<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use App\Models\TimeBlock;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Australia/Sydney';

    private Service $service;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'booking.lead_time_hours' => 24,
            'booking.max_advance_days' => 60,
            'booking.slot_granularity_minutes' => 15,
            'booking.buffer_minutes' => 15,
        ]);

        // Freeze "now": a Monday 09:00 Sydney time, well clear of DST edges.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 09:00', self::TZ));

        $this->service = Service::create([
            'slug' => 'test-drip', 'category' => 'Test', 'name' => 'Test Drip',
            'duration_minutes' => 60, 'price_cents' => 10000,
        ]);

        // Tuesday hours 09:00–17:00 (2026-06-16 is a Tuesday).
        AvailabilityRule::create(['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']);

        $this->availability = app(AvailabilityService::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function day(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::TZ);
    }

    private function slotTimes(string $date): array
    {
        return $this->availability->slotsForDate($this->service, $this->day($date))
            ->map(fn ($s) => $s->format('H:i'))->all();
    }

    public function test_emits_grid_slots_within_opening_hours(): void
    {
        $slots = $this->slotTimes('2026-06-16');

        $this->assertSame('09:00', $slots[0]);
        // 60-min service must END by close: last start is 16:00.
        $this->assertSame('16:00', end($slots));
        // 09:00..16:00 on a 15-min grid = 29 starts.
        $this->assertCount(29, $slots);
    }

    public function test_day_without_rule_has_no_slots(): void
    {
        $this->assertSame([], $this->slotTimes('2026-06-17')); // Wednesday — no rule
    }

    public function test_lead_time_hides_too_soon_slots(): void
    {
        // "Now" is Monday 09:00; lead time 24h → Tuesday slots before 09:00
        // would be hidden, but the first slot IS 09:00 (exactly at the cutoff
        // boundary it must still be visible only if >= windowStart).
        $slots = $this->slotTimes('2026-06-16');
        $this->assertSame('09:00', $slots[0]);

        config(['booking.lead_time_hours' => 26]); // cutoff Tue 11:00
        $slots = $this->slotTimes('2026-06-16');
        $this->assertSame('11:00', $slots[0]);
    }

    public function test_max_advance_hides_far_future_days(): void
    {
        config(['booking.max_advance_days' => 7]);
        // Tuesday two weeks out is beyond the window.
        $this->assertSame([], $this->slotTimes('2026-06-30'));
    }

    public function test_time_block_removes_overlapping_slots(): void
    {
        TimeBlock::create([
            'starts_at' => $this->day('2026-06-16 12:00')->utc(),
            'ends_at' => $this->day('2026-06-16 13:00')->utc(),
            'reason' => 'Lunch',
        ]);

        $slots = $this->slotTimes('2026-06-16');

        // Any slot whose lock window (60 + 15 buffer) overlaps 12:00–13:00
        // disappears: starts 11:00 (locks to 12:15) through 12:45.
        $this->assertContains('10:45', $slots);
        $this->assertNotContains('11:00', $slots);
        $this->assertNotContains('12:00', $slots);
        $this->assertNotContains('12:45', $slots);
        $this->assertContains('13:00', $slots);
    }

    public function test_blocking_booking_removes_slots_but_expired_hold_does_not(): void
    {
        $make = fn (string $status, $holdExpires) => Booking::create([
            'reference' => Booking::generateReference(),
            'service_id' => $this->service->id,
            'starts_at' => $this->day('2026-06-16 14:00')->utc(),
            'ends_at' => $this->day('2026-06-16 15:15')->utc(), // 60 + 15 buffer
            'status' => $status,
            'customer_name' => 'T', 'customer_email' => 't@t.test', 'customer_phone' => '0',
            'hold_expires_at' => $holdExpires,
        ]);

        $booking = $make(BookingStatus::Confirmed->value, null);
        $this->assertNotContains('14:00', $this->slotTimes('2026-06-16'));
        $this->assertNotContains('13:15', $this->slotTimes('2026-06-16')); // locks into 14:00
        $this->assertContains('15:15', $this->slotTimes('2026-06-16'));

        // An expired pending hold frees the slot even before the sweep runs.
        $booking->delete();
        $make(BookingStatus::PendingPayment->value, CarbonImmutable::now()->subMinute());
        $this->assertContains('14:00', $this->slotTimes('2026-06-16'));
    }

    public function test_grid_keeps_booked_slots_but_marks_them_unavailable(): void
    {
        Booking::create([
            'reference' => Booking::generateReference(),
            'service_id' => $this->service->id,
            'starts_at' => $this->day('2026-06-16 14:00')->utc(),
            'ends_at' => $this->day('2026-06-16 15:15')->utc(), // 60 + 15 buffer
            'status' => BookingStatus::Confirmed->value,
            'customer_name' => 'T', 'customer_email' => 't@t.test', 'customer_phone' => '0',
        ]);

        $grid = $this->availability->slotGridForDate($this->service, $this->day('2026-06-16'));

        // 14:00 is still in the grid (not removed) but flagged unavailable.
        $booked = $grid->first(fn ($s) => $s['start']->format('H:i') === '14:00');
        $this->assertNotNull($booked, '14:00 should still appear in the grid');
        $this->assertFalse($booked['available']);

        // A free slot is still available.
        $free = $grid->first(fn ($s) => $s['start']->format('H:i') === '09:00');
        $this->assertTrue($free['available']);

        // But it must NOT be bookable, so the booking still can't be made.
        $this->assertFalse($this->availability->isBookable($this->service, $this->day('2026-06-16 14:00')));

        // The range endpoint shape carries the flag through.
        $range = $this->availability->slotsForRange($this->service, $this->day('2026-06-16'), $this->day('2026-06-16'));
        $row = collect($range['2026-06-16'])->firstWhere('time', '14:00');
        $this->assertSame(['time' => '14:00', 'available' => false], $row);
    }

    public function test_is_bookable_validates_exact_slot(): void
    {
        $this->assertTrue($this->availability->isBookable($this->service, $this->day('2026-06-16 09:00')));
        $this->assertFalse($this->availability->isBookable($this->service, $this->day('2026-06-16 09:05'))); // off-grid
        $this->assertFalse($this->availability->isBookable($this->service, $this->day('2026-06-16 16:30'))); // would end past close
        $this->assertFalse($this->availability->isBookable($this->service, $this->day('2026-06-17 09:00'))); // no rule
    }

    public function test_dst_transition_day_keeps_wall_clock_slots(): void
    {
        // Sydney DST starts 2026-10-04 (Sunday): 02:00 → 03:00.
        config(['booking.max_advance_days' => 180]); // bring October into the window
        AvailabilityRule::create(['day_of_week' => 0, 'start_time' => '09:00', 'end_time' => '17:00']);

        $slots = $this->slotTimes('2026-10-04');

        // Wall-clock hours are unaffected (transition is at 2am): same grid.
        $this->assertSame('09:00', $slots[0]);
        $this->assertSame('16:00', end($slots));
        $this->assertCount(29, $slots);

        // And the first slot is a true +11:00 instant after the change.
        $first = $this->availability->slotsForDate($this->service, $this->day('2026-10-04'))->first();
        $this->assertSame('+11:00', $first->format('P'));
    }
}
