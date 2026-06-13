<?php

namespace App\Payments;

use App\Models\Booking;
use Square\Checkout\PaymentLinks\Requests\CreatePaymentLinkRequest;
use Square\Environments;
use Square\Exceptions\SquareApiException;
use Square\Exceptions\SquareException;
use Square\Payments\Requests\GetPaymentsRequest;
use Square\SquareClient;
use Square\Types\CheckoutOptions;
use Square\Types\Currency;
use Square\Types\Money;
use Square\Types\PrePopulatedData;
use Square\Types\QuickPay;

class SquareGateway implements PaymentGateway
{
    private function client(): SquareClient
    {
        $base = config('booking.square.environment') === 'production'
            ? Environments::Production->value
            : Environments::Sandbox->value;

        return new SquareClient(
            token: config('booking.square.access_token'),
            options: ['baseUrl' => $base],
        );
    }

    public function createCheckoutLink(Booking $booking, string $returnUrl, string $idempotencyKey): CheckoutLink
    {
        $response = $this->client()->checkout->paymentLinks->create(new CreatePaymentLinkRequest([
            'idempotencyKey' => $idempotencyKey,
            'description' => $booking->service->name.' — '.$booking->reference,
            'quickPay' => new QuickPay([
                'name' => $booking->service->name,
                'priceMoney' => new Money([
                    'amount' => $booking->service->price_cents,
                    'currency' => Currency::Aud->value,
                ]),
                'locationId' => config('booking.square.location_id'),
            ]),
            'checkoutOptions' => new CheckoutOptions([
                'redirectUrl' => $returnUrl,
                'askForShippingAddress' => false,
            ]),
            'prePopulatedData' => new PrePopulatedData([
                'buyerEmail' => $booking->customer_email,
            ]),
            'paymentNote' => $booking->reference,
        ]));

        $link = $response->getPaymentLink();
        if ($link === null || ! $link->getUrl()) {
            throw new \RuntimeException('Square did not return a payment link URL.');
        }

        return new CheckoutLink(
            id: (string) $link->getId(),
            orderId: (string) $link->getOrderId(),
            url: $link->getUrl(),
            raw: json_decode(json_encode($response), true) ?: [],
        );
    }

    public function fetchPayment(string $paymentId): ?array
    {
        try {
            $response = $this->client()->payments->get(new GetPaymentsRequest([
                'paymentId' => $paymentId,
            ]));
        } catch (SquareApiException|SquareException) {
            return null;
        }

        $payment = $response->getPayment();
        if ($payment === null) {
            return null;
        }

        return json_decode(json_encode($payment), true) ?: null;
    }
}
