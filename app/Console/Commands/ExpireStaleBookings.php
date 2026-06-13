<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireStaleBookings extends Command
{
    protected $signature = 'bookings:expire-stale';

    protected $description = 'Release slots held by pending bookings whose payment hold has lapsed';

    public function handle(): int
    {
        // Guarded transition: only flips rows that are still pending AND
        // expired at update time, so a concurrent payment confirmation
        // can never be clobbered (payment wins).
        $released = Booking::where('status', BookingStatus::PendingPayment)
            ->where('hold_expires_at', '<', now())
            ->update(['status' => BookingStatus::Abandoned, 'updated_at' => now()]);

        if ($released > 0) {
            $this->info("Released {$released} stale slot hold(s).");
        }

        return self::SUCCESS;
    }
}
