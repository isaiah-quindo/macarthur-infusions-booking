<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:expire-stale')->everyMinute();

// Send "your appointment is tomorrow" reminders once a day, mid-morning
// clinic time. Idempotent: each booking is stamped reminder_sent_at when
// dispatched, so a repeat firing won't double-send.
Schedule::command('bookings:send-reminders')
    ->dailyAt('09:00')
    ->timezone(config('booking.clinic_timezone'));
