<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Models\Booking;
use App\Models\BookingConsent;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /** Landing page: flat list of active services + category filter tabs. */
    public function services()
    {
        $services = Service::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $categories = $services->pluck('category')->unique()->values();

        return view('booking.services', [
            'services' => $services,
            'categories' => $categories,
        ]);
    }

    /** Slot picker + customer details form for one service. */
    public function create(Service $service)
    {
        abort_unless($service->is_active, 404);

        return view('booking.create', [
            'service' => $service,
            'clinicTz' => $this->availability->clinicTimezone(),
            'allowInPerson' => (bool) config('booking.allow_pay_in_person'),
            'squareConfigured' => (bool) config('booking.square.application_id'),
        ]);
    }

    /**
     * Creates the booking. Pay-online holds the slot pending payment;
     * pay-in-person confirms the slot immediately.
     */
    public function store(StoreBookingRequest $request, Service $service)
    {
        abort_unless($service->is_active, 404);

        $method = PaymentMethod::from($request->validated('payment_method'));
        abort_if($method === PaymentMethod::InPerson && ! config('booking.allow_pay_in_person'), 403);

        $start = CarbonImmutable::parse(
            $request->validated('date').' '.$request->validated('time'),
            $this->availability->clinicTimezone(),
        );

        if (! $this->availability->isBookable($service, $start)) {
            return back()->withInput()->with('error',
                'That time is no longer available — please choose another slot.');
        }

        $lockMinutes = $service->duration_minutes + (int) config('booking.buffer_minutes');
        $inPerson = $method === PaymentMethod::InPerson;

        try {
            // Advisory lock + re-check inside the transaction. Capacity races
            // resolve here (was previously the DB exclusion constraint).
            $booking = DB::transaction(function () use ($service, $start, $lockMinutes, $inPerson, $method, $request) {
                $this->availability->lockBookings();

                if (! $this->availability->canFitBooking($service, $start)) {
                    throw new SlotUnavailableException;
                }

                $booking = Booking::create([
                    'reference' => Booking::generateReference(),
                    'service_id' => $service->id,
                    'starts_at' => $start->utc(),
                    // ends_at is the slot LOCK end (includes buffer) — see AvailabilityService.
                    'ends_at' => $start->addMinutes($lockMinutes)->utc(),
                    'status' => $inPerson ? BookingStatus::Confirmed : BookingStatus::PendingPayment,
                    'payment_method' => $method,
                    'customer_name' => $request->validated('customer_name'),
                    'customer_email' => $request->validated('customer_email'),
                    'customer_phone' => $request->validated('customer_phone'),
                    'notes' => $request->validated('notes'),
                    'hold_expires_at' => $inPerson
                        ? null
                        : CarbonImmutable::now()->addMinutes(config('booking.hold_minutes')),
                ]);

                // Snapshot the consent at the exact text version the patient
                // just saw. Persisted in the same transaction so a booking
                // without a consent row is impossible.
                BookingConsent::create([
                    'booking_id' => $booking->id,
                    'privacy_policy_version' => config('booking.legal.privacy_policy_version'),
                    'collection_notice_version' => config('booking.legal.collection_notice_version'),
                    'consented_at' => CarbonImmutable::now(),
                    'consent_ip' => $request->ip(),
                    'consent_user_agent' => substr((string) $request->userAgent(), 0, 500),
                ]);

                return $booking;
            });
        } catch (SlotUnavailableException) {
            return back()->withInput()->with('error',
                'That time just filled up — please choose another slot.');
        }

        $this->grantAccess($request, $booking);

        if ($inPerson) {
            Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking));
            Mail::to(User::adminEmails())->send(new NewBookingAlertMail($booking));

            return redirect()->route('booking.show', $booking)
                ->with('status', "You're booked! Please bring payment to your appointment.");
        }

        return redirect()->route('booking.pay', $booking);
    }

    /** Booking detail / confirmation page (guarded by session or email). */
    public function show(Request $request, Booking $booking)
    {
        if (! $this->hasAccess($request, $booking)) {
            return view('booking.verify', ['booking' => $booking]);
        }

        return view('booking.show', ['booking' => $booking->load('service', 'payments')]);
    }

    /** Email gate for direct visits to a booking link. */
    public function verify(Request $request, Booking $booking)
    {
        $request->validate(['email' => ['required', 'email']]);

        if (! hash_equals(strtolower($booking->customer_email), strtolower($request->input('email')))) {
            return back()->with('error', "That email doesn't match this booking.");
        }

        $this->grantAccess($request, $booking);

        return redirect()->route('booking.show', $booking);
    }

    private function grantAccess(Request $request, Booking $booking): void
    {
        $request->session()->push('booking_access', $booking->id);
    }

    private function hasAccess(Request $request, Booking $booking): bool
    {
        return in_array($booking->id, $request->session()->get('booking_access', []), true);
    }
}
