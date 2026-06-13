<?php

namespace App\Payments;

use App\Models\Booking;

interface PaymentGateway
{
    /**
     * Create a hosted checkout link for this booking. Square returns a URL
     * we redirect the customer to; they pay on Square's page and are sent
     * back to $returnUrl.
     */
    public function createCheckoutLink(Booking $booking, string $returnUrl, string $idempotencyKey): CheckoutLink;

    /**
     * Look up a Square Payment by id. Returns the payment as an array,
     * or null if it can't be retrieved. Used by the return URL and the
     * webhook to confirm a payment is real before trusting it.
     */
    public function fetchPayment(string $paymentId): ?array;
}
