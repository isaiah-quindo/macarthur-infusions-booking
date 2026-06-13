<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ConfirmPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Square\Utils\WebhooksHelper;

class SquareController extends Controller
{
    public function __construct(private readonly ConfirmPaymentService $confirm) {}

    public function __invoke(Request $request): Response
    {
        $signatureKey = (string) config('booking.square.webhook_signature_key');
        $notificationUrl = (string) config('booking.square.webhook_notification_url') ?: $request->fullUrl();

        if ($signatureKey === '') {
            Log::error('Square webhook arrived but SQUARE_WEBHOOK_SIGNATURE_KEY is not set.');

            return response('Webhook not configured', 500);
        }

        $signature = (string) $request->header('x-square-hmacsha256-signature', '');
        $body = $request->getContent();

        if (! WebhooksHelper::verifySignature($body, $signature, $signatureKey, $notificationUrl)) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($body, true) ?: [];
        $type = $event['type'] ?? '';

        if (! in_array($type, ['payment.created', 'payment.updated'], true)) {
            // Acknowledge — Square retries non-2xx responses.
            return response('Ignored', 200);
        }

        $payment = $event['data']['object']['payment'] ?? null;
        $paymentId = $payment['id'] ?? null;
        $orderId = $payment['order_id'] ?? null;
        $status = $payment['status'] ?? null;

        if (! $paymentId || ! $orderId || ! in_array($status, ['COMPLETED', 'APPROVED'], true)) {
            return response('Not actionable', 200);
        }

        $booking = Booking::where('payment_link_order_id', $orderId)->first();
        if (! $booking) {
            // Payment for an order we don't recognise — could be a test event
            // or a payment created outside the booking flow. Acknowledge so
            // Square stops retrying.
            return response('Unknown order', 200);
        }

        $this->confirm->confirm($booking, $paymentId);

        return response('OK', 200);
    }
}
