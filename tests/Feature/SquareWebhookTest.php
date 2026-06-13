<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmationMail;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Payments\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SquareWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Australia/Sydney';
    private const SIGNING_KEY = 'webhook-signing-key';

    private Booking $booking;
    private string $notificationUrl;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 09:00', self::TZ));
        $this->notificationUrl = 'http://localhost/webhooks/square';
        config()->set('booking.square.webhook_signature_key', self::SIGNING_KEY);
        config()->set('booking.square.webhook_notification_url', $this->notificationUrl);

        $service = Service::create([
            'slug' => 'iron-infusion', 'category' => 'IV', 'name' => 'Iron Infusion',
            'duration_minutes' => 60, 'price_cents' => 24900,
        ]);
        AvailabilityRule::create(['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']);

        $start = CarbonImmutable::parse('2026-06-16 10:00', self::TZ);
        $this->booking = Booking::create([
            'reference' => Booking::generateReference(),
            'service_id' => $service->id,
            'starts_at' => $start->utc(),
            'ends_at' => $start->addMinutes(75)->utc(),
            'status' => BookingStatus::PendingPayment,
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '0400 000 000',
            'hold_expires_at' => now()->addMinutes(10),
            'payment_link_order_id' => 'order-1',
            'payment_link_id' => 'link-1',
            'payment_link_url' => 'https://square.link/u/abc',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function sign(string $body): string
    {
        return base64_encode(hash_hmac('sha256', $this->notificationUrl.$body, self::SIGNING_KEY, true));
    }

    public function test_valid_payment_updated_confirms_booking(): void
    {
        $this->mock(PaymentGateway::class)
            ->shouldReceive('fetchPayment')->with('pay-1')
            ->andReturn(['id' => 'pay-1', 'order_id' => 'order-1', 'status' => 'COMPLETED']);

        $body = json_encode([
            'type' => 'payment.updated',
            'data' => ['object' => ['payment' => [
                'id' => 'pay-1', 'order_id' => 'order-1', 'status' => 'COMPLETED',
            ]]],
        ]);

        $this->call('POST', $this->notificationUrl, [], [], [], [
            'HTTP_X_SQUARE_HMACSHA256_SIGNATURE' => $this->sign($body),
            'CONTENT_TYPE' => 'application/json',
        ], $body)
            ->assertOk();

        $this->assertSame(BookingStatus::Confirmed, $this->booking->fresh()->status);
        $this->assertSame(Payment::STATUS_COMPLETED, Payment::sole()->status);
        Mail::assertSent(BookingConfirmationMail::class);
    }

    public function test_bad_signature_is_rejected(): void
    {
        $this->mock(PaymentGateway::class)->shouldNotReceive('fetchPayment');

        $body = json_encode(['type' => 'payment.updated']);

        $this->call('POST', $this->notificationUrl, [], [], [], [
            'HTTP_X_SQUARE_HMACSHA256_SIGNATURE' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $body)
            ->assertStatus(400);

        $this->assertSame(BookingStatus::PendingPayment, $this->booking->fresh()->status);
    }

    public function test_unknown_order_is_acknowledged_but_does_nothing(): void
    {
        $this->mock(PaymentGateway::class)->shouldNotReceive('fetchPayment');

        $body = json_encode([
            'type' => 'payment.updated',
            'data' => ['object' => ['payment' => [
                'id' => 'pay-x', 'order_id' => 'order-unknown', 'status' => 'COMPLETED',
            ]]],
        ]);

        $this->call('POST', $this->notificationUrl, [], [], [], [
            'HTTP_X_SQUARE_HMACSHA256_SIGNATURE' => $this->sign($body),
            'CONTENT_TYPE' => 'application/json',
        ], $body)
            ->assertOk();

        $this->assertSame(0, Payment::count());
    }
}
