<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Payments\CheckoutLink;
use App\Payments\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Australia/Sydney';

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 09:00', self::TZ));
        config()->set('booking.square.access_token', 'sq-test-token');
        config()->set('booking.square.location_id', 'L-TEST');

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
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function stubLink(string $orderId = 'order-1', string $url = 'https://square.link/u/abc'): void
    {
        $this->mock(PaymentGateway::class)
            ->shouldReceive('createCheckoutLink')->once()
            ->andReturn(new CheckoutLink(
                id: 'link-1',
                orderId: $orderId,
                url: $url,
                raw: ['payment_link' => ['id' => 'link-1']],
            ));
    }

    private function stubFetchPayment(string $paymentId, ?array $payment): void
    {
        $this->mock(PaymentGateway::class)
            ->shouldReceive('fetchPayment')->with($paymentId)->andReturn($payment);
    }

    public function test_pay_landing_renders_with_continue_button(): void
    {
        $this->get(route('booking.pay', $this->booking))
            ->assertOk()
            ->assertSee('Continue to Square')
            ->assertSee('Your slot is held');
    }

    public function test_redirect_creates_link_and_stores_it_on_booking(): void
    {
        $this->stubLink('order-1', 'https://square.link/u/abc');

        $this->post(route('booking.pay.redirect', $this->booking))
            ->assertRedirect('https://square.link/u/abc');

        $fresh = $this->booking->fresh();
        $this->assertSame('order-1', $fresh->payment_link_order_id);
        $this->assertSame('https://square.link/u/abc', $fresh->payment_link_url);
        $this->assertSame('link-1', $fresh->payment_link_id);
    }

    public function test_redirect_reuses_existing_link(): void
    {
        $this->booking->update([
            'payment_link_id' => 'link-x',
            'payment_link_order_id' => 'order-x',
            'payment_link_url' => 'https://square.link/u/existing',
        ]);

        // createCheckoutLink must NOT be called.
        $this->mock(PaymentGateway::class)->shouldNotReceive('createCheckoutLink');

        $this->post(route('booking.pay.redirect', $this->booking))
            ->assertRedirect('https://square.link/u/existing');
    }

    public function test_return_confirms_booking_and_sends_emails(): void
    {
        $this->booking->update([
            'payment_link_id' => 'link-1',
            'payment_link_order_id' => 'order-1',
            'payment_link_url' => 'https://square.link/u/abc',
        ]);

        $this->stubFetchPayment('pay-1', [
            'id' => 'pay-1',
            'order_id' => 'order-1',
            'status' => 'COMPLETED',
        ]);

        $this->get(route('booking.payment.return', ['booking' => $this->booking, 'transactionId' => 'pay-1']))
            ->assertRedirect(route('booking.show', $this->booking));

        $this->assertSame(BookingStatus::Confirmed, $this->booking->fresh()->status);

        $payment = Payment::sole();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame('pay-1', $payment->square_payment_id);
        $this->assertSame(24900, $payment->amount_cents);

        Mail::assertSent(BookingConfirmationMail::class, fn ($m) => $m->hasTo('jane@example.com'));
        Mail::assertSent(NewBookingAlertMail::class, fn ($m) => $m->hasTo(config('booking.nurse_notification_email')));
    }

    public function test_return_rejects_transaction_id_for_a_different_order(): void
    {
        $this->booking->update([
            'payment_link_order_id' => 'order-1',
            'payment_link_url' => 'https://square.link/u/abc',
        ]);

        // Square returns a payment that points to a DIFFERENT order — must be ignored.
        $this->stubFetchPayment('pay-evil', [
            'id' => 'pay-evil',
            'order_id' => 'order-someone-else',
            'status' => 'COMPLETED',
        ]);

        $this->get(route('booking.payment.return', ['booking' => $this->booking, 'transactionId' => 'pay-evil']))
            ->assertRedirect(route('booking.show', $this->booking));

        $this->assertSame(BookingStatus::PendingPayment, $this->booking->fresh()->status);
        $this->assertSame(0, Payment::count());
        Mail::assertNothingSent();
    }

    public function test_return_with_no_transaction_id_shows_retry_message(): void
    {
        $this->booking->update([
            'payment_link_order_id' => 'order-1',
            'payment_link_url' => 'https://square.link/u/abc',
        ]);

        $this->mock(PaymentGateway::class)->shouldNotReceive('fetchPayment');

        $this->get(route('booking.payment.return', $this->booking))
            ->assertRedirect(route('booking.show', $this->booking))
            ->assertSessionHas('error');

        $this->assertSame(BookingStatus::PendingPayment, $this->booking->fresh()->status);
    }

    public function test_double_confirmation_only_creates_one_payment_row(): void
    {
        $this->booking->update([
            'payment_link_order_id' => 'order-1',
            'payment_link_url' => 'https://square.link/u/abc',
        ]);

        $this->mock(PaymentGateway::class)
            ->shouldReceive('fetchPayment')->with('pay-1')
            ->andReturn(['id' => 'pay-1', 'order_id' => 'order-1', 'status' => 'COMPLETED']);

        // Return URL fires.
        $this->get(route('booking.payment.return', ['booking' => $this->booking, 'transactionId' => 'pay-1']));
        // Webhook fires for the same payment (simulated as a second return-URL hit).
        $this->get(route('booking.payment.return', ['booking' => $this->booking, 'transactionId' => 'pay-1']));

        $this->assertSame(1, Payment::count());
        Mail::assertSent(BookingConfirmationMail::class, 1);
    }

    public function test_expired_hold_is_rejected_before_creating_link(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(11));

        $this->mock(PaymentGateway::class)->shouldNotReceive('createCheckoutLink');

        $this->post(route('booking.pay.redirect', $this->booking))
            ->assertRedirect(route('booking.create', $this->booking->service))
            ->assertSessionHas('error');
    }
}
