<?php

namespace App\Services;

use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\ClinicSetting;
use App\Models\RecurringBlock;
use App\Models\Service;
use App\Models\TimeBlock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes bookable slots: weekly rules − time blocks − slot-blocking
 * bookings, on the configured granularity grid, within the lead-time /
 * max-advance window.
 *
 * All wall-clock maths happen in the clinic timezone (DST-safe);
 * comparisons against stored bookings happen in absolute time.
 *
 * Note: bookings.ends_at INCLUDES the post-appointment buffer — it is the
 * slot lock end, not the clinical end. The customer-facing end time is
 * starts_at + service duration.
 */
class AvailabilityService
{
    public function clinicTimezone(): string
    {
        return config('booking.clinic_timezone');
    }

    /** Earliest bookable instant (lead time applied). */
    public function windowStart(): CarbonImmutable
    {
        return CarbonImmutable::now($this->clinicTimezone())
            ->addHours(config('booking.lead_time_hours'));
    }

    /** Latest bookable instant. */
    public function windowEnd(): CarbonImmutable
    {
        return CarbonImmutable::now($this->clinicTimezone())
            ->addDays(ClinicSetting::current()->max_advance_days)
            ->endOfDay();
    }

    /**
     * Every offered slot for one clinic-local date, each flagged available
     * or not. A slot is OFFERED if it falls inside opening hours and the
     * booking window (lead time / max advance); it is UNAVAILABLE (but still
     * shown, disabled) when a block or another booking clashes with it.
     *
     * @return Collection<int, array{start: CarbonImmutable, available: bool}>
     */
    public function slotGridForDate(Service $service, CarbonImmutable $date): Collection
    {
        $tz = $this->clinicTimezone();
        $date = $date->setTimezone($tz)->startOfDay();

        $rules = AvailabilityRule::where('day_of_week', $date->dayOfWeek)->get();
        if ($rules->isEmpty()) {
            return collect();
        }

        $granularity = (int) config('booking.slot_granularity_minutes');
        $lockMinutes = $service->duration_minutes + (int) config('booking.buffer_minutes');
        $windowStart = $this->windowStart();
        $windowEnd = $this->windowEnd();
        $capacity = ClinicSetting::current()->concurrent_capacity;

        $dayStartUtc = $date->utc();
        $dayEndUtc = $date->endOfDay()->utc();

        $blocks = TimeBlock::where('starts_at', '<', $dayEndUtc->addMinutes($lockMinutes))
            ->where('ends_at', '>', $dayStartUtc)
            ->get();

        // Recurring blocks for this weekday — converted to concrete clinic-local
        // datetimes for THIS date so the same overlap check works for both kinds.
        $recurringBlocks = RecurringBlock::where('day_of_week', $date->dayOfWeek)->get()
            ->map(fn (RecurringBlock $r) => [
                'starts_at' => $date->setTimeFromTimeString(substr($r->start_time, 0, 5)),
                'ends_at' => $date->setTimeFromTimeString(substr($r->end_time, 0, 5)),
            ]);

        $bookings = Booking::blocking()
            ->where('starts_at', '<', $dayEndUtc->addMinutes($lockMinutes))
            ->where('ends_at', '>', $dayStartUtc)
            ->get()
            ->reject(fn (Booking $b) => $b->holdHasExpired());

        $slots = collect();

        foreach ($rules as $rule) {
            $open = $date->setTimeFromTimeString(substr($rule->start_time, 0, 5));
            $close = $date->setTimeFromTimeString(substr($rule->end_time, 0, 5));

            for ($start = $open; $start->addMinutes($service->duration_minutes)->lte($close); $start = $start->addMinutes($granularity)) {
                // Outside the booking window (too soon / too far) → not offered.
                if ($start->lt($windowStart) || $start->gt($windowEnd)) {
                    continue;
                }

                $lockEnd = $start->addMinutes($lockMinutes);

                // Blocks (one-off or recurring) always close the slot,
                // regardless of capacity.
                $blocked = $blocks->contains(fn ($b) => $b->starts_at->lt($lockEnd) && $b->ends_at->gt($start))
                    || $recurringBlocks->contains(fn ($r) => $r['starts_at']->lt($lockEnd) && $r['ends_at']->gt($start));

                // Otherwise: slot is available while concurrent bookings < capacity.
                $overlapping = $bookings->filter(fn ($b) => $b->starts_at->lt($lockEnd) && $b->ends_at->gt($start))->count();
                $clash = $blocked || $overlapping >= $capacity;

                $slots->push(['start' => $start, 'available' => ! $clash]);
            }
        }

        return $slots->unique(fn ($s) => $s['start']->timestamp)
            ->sortBy(fn ($s) => $s['start']->timestamp)
            ->values();
    }

    /**
     * Bookable (free) slot start times for one clinic-local date.
     *
     * @return Collection<int, CarbonImmutable> clinic-tz starts
     */
    public function slotsForDate(Service $service, CarbonImmutable $date): Collection
    {
        return $this->slotGridForDate($service, $date)
            ->where('available', true)
            ->map(fn (array $s) => $s['start'])
            ->values();
    }

    /**
     * Map of date string => offered slots ("H:i" + availability) for a range.
     * Filled slots are returned too (available=false) so the UI can show them
     * disabled rather than dropping them.
     *
     * @return array<string, list<array{time: string, available: bool}>>
     */
    public function slotsForRange(Service $service, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tz = $this->clinicTimezone();
        $from = $from->setTimezone($tz)->startOfDay();
        $to = $to->setTimezone($tz)->startOfDay();

        $out = [];
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $out[$day->toDateString()] = $this->slotGridForDate($service, $day)
                ->map(fn (array $s) => ['time' => $s['start']->format('H:i'), 'available' => $s['available']])
                ->all();
        }

        return $out;
    }

    /** Re-validates a specific slot at submit time. */
    public function isBookable(Service $service, CarbonImmutable $clinicStart): bool
    {
        return $this->slotsForDate($service, $clinicStart)
            ->contains(fn (CarbonImmutable $s) => $s->equalTo($clinicStart));
    }

    /**
     * Like isBookable() but lets callers exclude a specific booking from the
     * capacity count — used by reschedule / payment-confirm flows where a
     * booking row is already counting against itself.
     */
    public function canFitBooking(Service $service, CarbonImmutable $clinicStart, ?int $excludeBookingId = null): bool
    {
        $tz = $this->clinicTimezone();
        $start = $clinicStart->setTimezone($tz);
        $date = $start->startOfDay();

        // 1. Inside opening hours?
        $insideHours = AvailabilityRule::where('day_of_week', $date->dayOfWeek)->get()
            ->contains(function ($rule) use ($start, $service, $date) {
                $open = $date->setTimeFromTimeString(substr($rule->start_time, 0, 5));
                $close = $date->setTimeFromTimeString(substr($rule->end_time, 0, 5));

                return $start->gte($open) && $start->addMinutes($service->duration_minutes)->lte($close);
            });

        if (! $insideHours) {
            return false;
        }

        $lockMinutes = $service->duration_minutes + (int) config('booking.buffer_minutes');
        $lockEnd = $start->addMinutes($lockMinutes);

        // 2. No one-off block clashes?
        if (TimeBlock::where('starts_at', '<', $lockEnd->utc())->where('ends_at', '>', $start->utc())->exists()) {
            return false;
        }

        // 3. No recurring block clashes?
        $recurringClash = RecurringBlock::where('day_of_week', $start->dayOfWeek)->get()
            ->contains(function ($r) use ($start, $lockEnd, $date) {
                $rStart = $date->setTimeFromTimeString(substr($r->start_time, 0, 5));
                $rEnd = $date->setTimeFromTimeString(substr($r->end_time, 0, 5));

                return $rStart->lt($lockEnd) && $rEnd->gt($start);
            });

        if ($recurringClash) {
            return false;
        }

        // 4. Concurrent bookings under capacity?
        $overlapping = Booking::blocking()
            ->where('starts_at', '<', $lockEnd->utc())
            ->where('ends_at', '>', $start->utc())
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->get()
            ->reject(fn (Booking $b) => $b->holdHasExpired())
            ->count();

        return $overlapping < ClinicSetting::current()->concurrent_capacity;
    }

    /**
     * Serializes booking-creating transactions so the concurrent-capacity
     * check can't race with a parallel insert. Must be called inside an
     * active DB::transaction(); the lock is released on commit/rollback.
     */
    public function lockBookings(): void
    {
        DB::statement('SELECT pg_advisory_xact_lock(?)', [self::BOOKINGS_LOCK_KEY]);
    }

    /** Arbitrary, app-unique key for the booking-creation advisory lock. */
    private const BOOKINGS_LOCK_KEY = 79215438;
}
