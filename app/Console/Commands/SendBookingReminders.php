<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Mail\BookingReminderMail;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Email a reminder to each customer with a confirmed booking tomorrow';

    public function handle(): int
    {
        $tz = config('booking.clinic_timezone');
        $tomorrowStart = CarbonImmutable::now($tz)->addDay()->startOfDay();
        $tomorrowEnd = $tomorrowStart->endOfDay();

        // Pull bookings that:
        //   - sit inside tomorrow's clinic-local day
        //   - are confirmed (not pending_payment, not cancelled, abandoned, etc.)
        //   - haven't already had a reminder sent (idempotency safeguard)
        $bookings = Booking::with('service')
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [$tomorrowStart->utc(), $tomorrowEnd->utc()])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No reminders to send.');

            return self::SUCCESS;
        }

        foreach ($bookings as $booking) {
            Mail::to($booking->customer_email)->send(new BookingReminderMail($booking));
            $booking->update(['reminder_sent_at' => now()]);
        }

        $this->info("Sent {$bookings->count()} reminder(s).");

        return self::SUCCESS;
    }
}
