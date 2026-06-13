<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function show(Booking $booking)
    {
        return view('admin.bookings.show', [
            'booking' => $booking->load('service', 'payments'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $start = CarbonImmutable::parse($data['date'].' '.$data['time'], config('booking.clinic_timezone'));
        $lockMinutes = $service->duration_minutes + (int) config('booking.buffer_minutes');

        try {
            $booking = DB::transaction(function () use ($service, $start, $lockMinutes, $data) {
                $this->availability->lockBookings();

                if (! $this->availability->canFitBooking($service, $start)) {
                    throw new SlotUnavailableException;
                }

                return Booking::create([
                    'reference' => Booking::generateReference(),
                    'service_id' => $service->id,
                    'starts_at' => $start->utc(),
                    'ends_at' => $start->addMinutes($lockMinutes)->utc(),
                    'status' => BookingStatus::Confirmed,
                    'payment_method' => PaymentMethod::InPerson,
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (SlotUnavailableException) {
            return back()->withInput()->withErrors(['time' => 'No room left at that time — the clinic is at capacity.']);
        }

        return redirect()->route('admin.bookings.show', $booking)
            ->with('status', 'Manual booking created.');
    }

    public function update(Request $request, Booking $booking)
    {
        $action = $request->validate([
            'action' => ['required', 'in:complete,no_show,cancel,reschedule,mark_refunded,record_in_person_payment'],
        ])['action'];

        return match ($action) {
            'complete' => $this->transition($booking, BookingStatus::Completed, 'Marked completed.'),
            'no_show' => $this->transition($booking, BookingStatus::NoShow, 'Marked as no-show.'),
            'cancel' => $this->cancel($booking),
            'reschedule' => $this->reschedule($request, $booking),
            'mark_refunded' => $this->markRefunded($booking),
            'record_in_person_payment' => $this->recordInPersonPayment($booking),
        };
    }

    private function transition(Booking $booking, BookingStatus $to, string $message)
    {
        $booking->update(['status' => $to]);

        return back()->with('status', $message);
    }

    private function cancel(Booking $booking)
    {
        $booking->update(['status' => BookingStatus::Cancelled]);

        Mail::to($booking->customer_email)->send(new BookingCancelledMail($booking));

        $hasPaid = $booking->payments()->where('status', Payment::STATUS_COMPLETED)->exists();

        return back()->with('status', 'Booking cancelled and the customer notified.'
            .($hasPaid ? ' Remember to refund the payment in the Square Dashboard, then mark it refunded here.' : ''));
    }

    private function reschedule(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        $start = CarbonImmutable::parse($data['date'].' '.$data['time'], config('booking.clinic_timezone'));
        $lockMinutes = $booking->service->duration_minutes + (int) config('booking.buffer_minutes');

        try {
            DB::transaction(function () use ($booking, $start, $lockMinutes) {
                $this->availability->lockBookings();

                if (! $this->availability->canFitBooking($booking->service, $start, excludeBookingId: $booking->id)) {
                    throw new SlotUnavailableException;
                }

                $booking->update([
                    'starts_at' => $start->utc(),
                    'ends_at' => $start->addMinutes($lockMinutes)->utc(),
                ]);
            });
        } catch (SlotUnavailableException) {
            return back()->with('error', 'No room left at that time — the clinic is at capacity.');
        }

        return back()->with('status', 'Booking rescheduled — let the customer know.');
    }

    private function markRefunded(Booking $booking)
    {
        $booking->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->update(['status' => Payment::STATUS_REFUNDED]);

        return back()->with('status', 'Payment marked refunded.');
    }

    /** Records cash/card collected at the clinic for a pay-in-person booking. */
    private function recordInPersonPayment(Booking $booking)
    {
        if ($booking->isPaid()) {
            return back()->with('error', 'This booking is already paid.');
        }

        $booking->payments()->create([
            'amount_cents' => $booking->service->price_cents,
            'currency' => 'AUD',
            'method' => PaymentMethod::InPerson,
            'status' => Payment::STATUS_COMPLETED,
        ]);

        return back()->with('status', 'In-person payment recorded.');
    }
}
