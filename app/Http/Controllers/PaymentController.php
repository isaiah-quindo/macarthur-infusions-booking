<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Payments\PaymentGateway;
use App\Services\ConfirmPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ConfirmPaymentService $confirm,
    ) {}

    /** Landing page with hold countdown + "Continue to Square" button. */
    public function show(Request $request, Booking $booking)
    {
        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        return view('booking.pay', [
            'booking' => $booking->load('service'),
            'squareConfigured' => $this->squareConfigured(),
        ]);
    }

    /** Creates the hosted checkout link (or reuses one) and redirects to Square. */
    public function redirect(Request $request, Booking $booking)
    {
        if ($redirect = $this->rejectIfNotPayable($booking)) {
            return $redirect;
        }

        if (! $this->squareConfigured()) {
            return back()->with('error', 'Online payments are not configured. Please contact us to complete your booking.');
        }

        // Reuse the link if we already created one for this booking — avoids
        // generating a fresh URL on every back-button / refresh.
        if ($booking->payment_link_url) {
            return redirect()->away($booking->payment_link_url);
        }

        try {
            $link = $this->gateway->createCheckoutLink(
                $booking,
                route('booking.payment.return', $booking),
                $booking->reference,
            );
        } catch (\Throwable $e) {
            Log::error('Square checkout link creation failed', [
                'booking' => $booking->reference,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'We could not start the payment. Please try again, or call us if it keeps failing.');
        }

        $booking->update([
            'payment_link_id' => $link->id,
            'payment_link_order_id' => $link->orderId,
            'payment_link_url' => $link->url,
        ]);

        return redirect()->away($link->url);
    }

    /** Square redirects the customer here after they pay. */
    public function return(Request $request, Booking $booking)
    {
        // Re-grant access — the user has been off-site, so the session may
        // not have the access flag anymore (different browser, etc.).
        $request->session()->push('booking_access', $booking->id);

        if ($booking->status === BookingStatus::Confirmed) {
            return redirect()->route('booking.show', $booking)
                ->with('status', 'Payment received — your appointment is confirmed!');
        }

        $paymentId = (string) $request->query('transactionId', '');
        if ($paymentId === '') {
            // Customer landed on /return with no transaction id (cancelled or
            // closed the Square tab and came back later). Show booking page;
            // the webhook is the safety net for any payment that did go through.
            return redirect()->route('booking.show', $booking)
                ->with('error', 'Payment was not completed. You can try again while your slot is still held.');
        }

        $confirmed = $this->confirm->confirm($booking, $paymentId);

        if ($confirmed || $booking->fresh()->status === BookingStatus::Confirmed) {
            return redirect()->route('booking.show', $booking)
                ->with('status', 'Payment received — your appointment is confirmed!');
        }

        // Payment isn't visible to Square's API yet (eventually consistent),
        // or it didn't succeed. The webhook will finalise if it later does.
        return redirect()->route('booking.show', $booking)
            ->with('status', 'Thanks — we are confirming your payment. You will get an email as soon as it lands.');
    }

    private function squareConfigured(): bool
    {
        return (bool) config('booking.square.access_token')
            && (bool) config('booking.square.location_id');
    }

    private function rejectIfNotPayable(Booking $booking)
    {
        if ($booking->status === BookingStatus::Confirmed) {
            return redirect()->route('booking.show', $booking);
        }

        if ($booking->status !== BookingStatus::PendingPayment || $booking->holdHasExpired()) {
            return redirect()->route('booking.create', $booking->service)
                ->with('error', 'Your slot hold has expired — please pick a time again.');
        }

        return null;
    }
}
