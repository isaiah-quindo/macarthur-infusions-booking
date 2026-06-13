<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $service = Service::create([
            'slug' => 'b12', 'category' => 'Injections', 'name' => 'B12 Injection',
            'duration_minutes' => 15, 'price_cents' => 4900,
        ]);

        $start = CarbonImmutable::parse('2026-06-16 10:00', 'Australia/Sydney');
        $this->booking = Booking::create([
            'reference' => Booking::generateReference(),
            'service_id' => $service->id,
            'starts_at' => $start->utc(),
            'ends_at' => $start->addMinutes(30)->utc(),
            'status' => BookingStatus::Confirmed,
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '0400 000 000',
        ]);
    }

    public function test_guests_and_non_admins_are_locked_out(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $patient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($patient)->get('/admin')->assertForbidden();
    }

    public function test_admin_dashboard_lists_todays_booking(): void
    {
        // Freeze "now" to the booking's day so it lands in the Today list.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-16 08:00', 'Australia/Sydney'));

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Today')
            ->assertSee('Jane Smith')
            ->assertSee('B12 Injection');

        CarbonImmutable::setTestNow();
    }

    public function test_admin_can_cancel_and_customer_is_emailed(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.update', $this->booking), ['action' => 'cancel'])
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Cancelled, $this->booking->fresh()->status);
        Mail::assertSent(BookingCancelledMail::class, fn ($m) => $m->hasTo('jane@example.com'));
    }

    public function test_admin_reschedule_respects_overlap_constraint(): void
    {
        $other = CarbonImmutable::parse('2026-06-16 11:00', 'Australia/Sydney');
        Booking::create([
            'reference' => Booking::generateReference(),
            'service_id' => $this->booking->service_id,
            'starts_at' => $other->utc(),
            'ends_at' => $other->addMinutes(30)->utc(),
            'status' => BookingStatus::Confirmed,
            'customer_name' => 'Other', 'customer_email' => 'o@example.com', 'customer_phone' => '0',
        ]);

        // Move onto the other booking → rejected.
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.update', $this->booking), [
                'action' => 'reschedule', 'date' => '2026-06-16', 'time' => '11:00',
            ])->assertSessionHas('error');

        // Move to a free time → ok.
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.update', $this->booking), [
                'action' => 'reschedule', 'date' => '2026-06-16', 'time' => '14:00',
            ])->assertSessionHas('status');

        $this->assertSame('14:00', $this->booking->fresh()->startsAtClinic()->format('H:i'));
    }

    public function test_admin_records_in_person_payment(): void
    {
        $this->booking->update(['payment_method' => 'in_person']);

        $this->assertFalse($this->booking->isPaid());

        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.update', $this->booking), ['action' => 'record_in_person_payment'])
            ->assertSessionHas('status');

        $payment = $this->booking->payments()->sole();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame(PaymentMethod::InPerson, $payment->method);
        $this->assertSame($this->booking->service->price_cents, $payment->amount_cents);
        $this->assertTrue($this->booking->fresh()->isPaid());

        // Recording again is rejected — no duplicate payment.
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.update', $this->booking), ['action' => 'record_in_person_payment'])
            ->assertSessionHas('error');
        $this->assertSame(1, $this->booking->payments()->count());
    }

    public function test_admin_manual_booking_and_service_update(): void
    {
        $this->actingAs($this->admin)->post(route('admin.bookings.store'), [
            'service_id' => $this->booking->service_id,
            'date' => '2026-06-17', 'time' => '09:00',
            'customer_name' => 'Walk In', 'customer_email' => 'walkin@example.com', 'customer_phone' => '0411',
        ])->assertSessionHas('status');

        $this->assertSame(BookingStatus::Confirmed, Booking::where('customer_name', 'Walk In')->sole()->status);

        $service = Service::where('slug', 'b12')->sole();
        $this->actingAs($this->admin)->patch(route('admin.services.update', $service->id), [
            'price_dollars' => 59.50, 'duration_minutes' => 20, 'is_active' => 1,
        ]);
        $service->refresh();
        $this->assertSame(5950, $service->price_cents);
        $this->assertSame(20, $service->duration_minutes);
    }
}
