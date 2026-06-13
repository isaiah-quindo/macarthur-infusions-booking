<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tz = config('booking.clinic_timezone');
        $now = CarbonImmutable::now($tz);

        $past = $request->boolean('past');

        if ($past) {
            $bookings = Booking::with('service', 'payments')
                ->where('starts_at', '<', $now->startOfDay()->utc())
                ->whereNotIn('status', [BookingStatus::Abandoned->value])
                ->orderByDesc('starts_at')
                ->limit(100)
                ->get()
                ->groupBy(fn (Booking $b) => $b->startsAtClinic()->toDateString());

            return view('admin.dashboard', ['past' => $past, 'now' => $now, 'pastBookings' => $bookings]);
        }

        // Today: everything scheduled today (except dropped holds), so the
        // nurse sees the full day including any same-day cancellations.
        $today = Booking::with('service', 'payments')
            ->whereBetween('starts_at', [$now->startOfDay()->utc(), $now->endOfDay()->utc()])
            ->whereNotIn('status', [BookingStatus::Abandoned->value])
            ->orderBy('starts_at')
            ->get();

        // Upcoming: live future bookings, grouped by date.
        $upcoming = Booking::with('service', 'payments')
            ->where('starts_at', '>', $now->endOfDay()->utc())
            ->whereNotIn('status', [BookingStatus::Abandoned->value, BookingStatus::Cancelled->value])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Booking $b) => $b->startsAtClinic()->toDateString());

        return view('admin.dashboard', [
            'past' => false,
            'now' => $now,
            'today' => $today,
            'upcoming' => $upcoming,
        ]);
    }
}
