<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Australia/Sydney';

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 09:00', self::TZ)); // Monday

        $this->service = Service::create([
            'slug' => 'myers-cocktail', 'category' => 'IV', 'name' => "Myers' Cocktail",
            'duration_minutes' => 60, 'price_cents' => 22900,
        ]);
        AvailabilityRule::create(['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '0400 000 000',
            'date' => '2026-06-16',
            'time' => '10:00',
            'notes' => 'First visit',
            'payment_method' => 'card',
            'consent_privacy' => '1',
            'website' => '',
        ], $overrides);
    }

    public function test_services_page_lists_active_services(): void
    {
        $this->get('/')->assertOk()->assertSee("Myers' Cocktail")->assertSee('$229');
    }

    public function test_availability_endpoint_returns_slots(): void
    {
        $this->getJson('/api/services/myers-cocktail/availability?from=2026-06-15&to=2026-06-21')
            ->assertOk()
            ->assertJsonPath('timezone', self::TZ)
            ->assertJsonPath('days.2026-06-16.0.time', '09:00')
            ->assertJsonPath('days.2026-06-16.0.available', true)
            ->assertJsonPath('days.2026-06-17', []);
    }

    public function test_booking_creates_pending_hold_and_redirects_to_payment(): void
    {
        $response = $this->post('/book/myers-cocktail', $this->payload());

        $booking = Booking::sole();
        $response->assertRedirect(route('booking.pay', $booking));

        $this->assertSame(BookingStatus::PendingPayment, $booking->status);
        $this->assertSame('2026-06-16 10:00', $booking->startsAtClinic()->format('Y-m-d H:i'));
        // 60 min + 15 buffer lock window.
        $this->assertSame(75, (int) $booking->starts_at->diffInMinutes($booking->ends_at));
        $this->assertTrue($booking->hold_expires_at->equalTo(now()->addMinutes(10)));
    }

    public function test_taken_slot_is_rejected_at_submit(): void
    {
        $this->post('/book/myers-cocktail', $this->payload());

        $this->post('/book/myers-cocktail', $this->payload(['customer_email' => 'other@example.com']))
            ->assertSessionHas('error');

        $this->assertSame(1, Booking::count());
    }

    public function test_honeypot_blocks_bots(): void
    {
        $this->post('/book/myers-cocktail', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Booking::count());
    }

    public function test_off_grid_or_out_of_hours_slot_is_rejected(): void
    {
        $this->post('/book/myers-cocktail', $this->payload(['time' => '10:07']))->assertSessionHas('error');
        $this->post('/book/myers-cocktail', $this->payload(['time' => '18:00']))->assertSessionHas('error');
        $this->assertSame(0, Booking::count());
    }

    public function test_booking_page_requires_email_verification_without_session(): void
    {
        $this->post('/book/myers-cocktail', $this->payload());
        $booking = Booking::sole();

        // Fresh session (no access) → email gate.
        $this->flushSession();
        $this->get(route('booking.show', $booking))->assertSee('confirm the email');

        $this->post(route('booking.verify', $booking), ['email' => 'wrong@example.com'])
            ->assertSessionHas('error');

        $this->post(route('booking.verify', $booking), ['email' => 'JANE@example.com'])
            ->assertRedirect(route('booking.show', $booking));
        $this->get(route('booking.show', $booking))->assertSee($booking->reference);
    }

    public function test_in_person_booking_confirms_immediately_without_a_hold(): void
    {
        Mail::fake();

        $response = $this->post('/book/myers-cocktail', $this->payload(['payment_method' => 'in_person']));

        $booking = Booking::sole();
        $response->assertRedirect(route('booking.show', $booking));

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentMethod::InPerson, $booking->payment_method);
        $this->assertNull($booking->hold_expires_at);
        $this->assertFalse($booking->isPaid());

        Mail::assertSent(BookingConfirmationMail::class);
        Mail::assertSent(NewBookingAlertMail::class);
    }

    public function test_admin_alert_is_sent_to_admin_users_not_a_fixed_address(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'admin', 'email' => 'nurse@clinic.test']);

        $this->post('/book/myers-cocktail', $this->payload(['payment_method' => 'in_person']));

        Mail::assertSent(NewBookingAlertMail::class, fn ($m) => $m->hasTo('nurse@clinic.test'));
    }

    public function test_in_person_confirmed_slot_blocks_others(): void
    {
        Mail::fake();

        $this->post('/book/myers-cocktail', $this->payload(['payment_method' => 'in_person']));

        // Same slot, any method → rejected by the confirmed booking.
        $this->post('/book/myers-cocktail', $this->payload([
            'payment_method' => 'card', 'customer_email' => 'other@example.com',
        ]))->assertSessionHas('error');

        $this->assertSame(1, Booking::count());
    }

    public function test_in_person_is_blocked_when_disabled(): void
    {
        config(['booking.allow_pay_in_person' => false]);

        $this->post('/book/myers-cocktail', $this->payload(['payment_method' => 'in_person']))
            ->assertForbidden();

        $this->assertSame(0, Booking::count());
    }

    public function test_payment_method_is_required_and_validated(): void
    {
        $this->post('/book/myers-cocktail', $this->payload(['payment_method' => 'bitcoin']))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Booking::count());
    }

    public function test_expiry_command_releases_stale_holds_and_slot_reopens(): void
    {
        $this->post('/book/myers-cocktail', $this->payload());
        $booking = Booking::sole();

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(11));
        $this->artisan('bookings:expire-stale')->assertSuccessful();

        $this->assertSame(BookingStatus::Abandoned, $booking->fresh()->status);

        // Slot is bookable again.
        $this->post('/book/myers-cocktail', $this->payload(['customer_email' => 'second@example.com']))
            ->assertSessionMissing('error');
        $this->assertSame(2, Booking::count());
    }
}
