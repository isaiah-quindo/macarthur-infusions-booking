<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\SlotUnavailableException;
use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Mail\PaymentNeedsReviewMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGateway;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Shared confirmation path for both the Square return URL and the webhook.
 * Idempotent — calling it twice with the same Square payment id is safe.
 */
class ConfirmPaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Returns true if this call confirmed the booking, false otherwise
     * (already confirmed, payment not yet final at Square, or invalid).
     */
    public function confirm(Booking $booking, string $squarePaymentId): bool
    {
        if (Payment::where('square_payment_id', $squarePaymentId)->exists()) {
            return false;
        }

        $squarePayment = $this->gateway->fetchPayment($squarePaymentId);
        if ($squarePayment === null) {
            return false;
        }

        $status = $squarePayment['status'] ?? null;
        if (! in_array($status, ['COMPLETED', 'APPROVED'], true)) {
            return false;
        }

        // Defence-in-depth: make sure this payment is for the order we
        // generated for THIS booking. Square won't redirect cross-booking,
        // but a forged transactionId in the return URL would.
        if (($squarePayment['order_id'] ?? null) !== $booking->payment_link_order_id) {
            return false;
        }

        try {
            $newlyConfirmed = DB::transaction(function () use ($booking, $squarePaymentId, $squarePayment) {
                // Serialize against parallel booking creates so the capacity
                // re-check below can't race with an inbound new booking.
                $this->availability->lockBookings();

                // If the sweep flipped this booking to abandoned during the
                // payment, make sure flipping it back to confirmed won't push
                // the slot over capacity.
                if ($booking->status === BookingStatus::Abandoned) {
                    $clinicStart = CarbonImmutable::parse($booking->starts_at)
                        ->setTimezone(config('booking.clinic_timezone'));
                    if (! $this->availability->canFitBooking($booking->service, $clinicStart, excludeBookingId: $booking->id)) {
                        throw new SlotUnavailableException;
                    }
                }

                try {
                    $booking->payments()->create([
                        'square_payment_id' => $squarePaymentId,
                        'amount_cents' => $booking->service->price_cents,
                        'currency' => 'AUD',
                        'method' => PaymentMethod::Card,
                        'status' => Payment::STATUS_COMPLETED,
                        'raw_response' => $squarePayment,
                    ]);
                } catch (QueryException $e) {
                    // Unique violation on square_payment_id — another path
                    // (webhook vs return URL) won the race. Nothing to do.
                    if (str_contains($e->getMessage(), 'square_payment_id')) {
                        return false;
                    }
                    throw $e;
                }

                // Payment wins — restore a booking the expiry sweep flipped
                // to abandoned while the customer was on Square's page.
                $booking->update(['status' => BookingStatus::Confirmed]);

                return true;
            });
        } catch (SlotUnavailableException) {
            // Worst-case race: hold lapsed AND the slot filled to capacity
            // while Square was processing. Money is captured, slot is gone.
            Mail::to(User::adminEmails())
                ->send(new PaymentNeedsReviewMail($booking->fresh('service')));

            return true; // counts as a confirmation event — caller can stop
        }

        if ($newlyConfirmed) {
            Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking->fresh()));
            Mail::to(User::adminEmails())->send(new NewBookingAlertMail($booking->fresh()));
        }

        return (bool) $newlyConfirmed;
    }
}
