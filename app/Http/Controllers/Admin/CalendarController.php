<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('admin.calendar');
    }

    /**
     * FullCalendar event feed. The library passes `start` and `end` as
     * ISO-8601 strings spanning the currently visible range; we return
     * every booking whose time window overlaps that range.
     */
    public function events(Request $request): JsonResponse
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $start = CarbonImmutable::parse($request->input('start'))->utc();
        $end = CarbonImmutable::parse($request->input('end'))->utc();

        $bookings = Booking::with('service')
            ->whereNotIn('status', [BookingStatus::Abandoned->value])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get();

        return response()->json(
            $bookings->map(fn (Booking $b) => $this->toEvent($b))->values()
        );
    }

    private function toEvent(Booking $b): array
    {
        // The exclusion-constraint end (ends_at) includes the post-booking
        // buffer — show the actual appointment length to the nurse instead.
        $startClinic = $b->startsAtClinic();
        $endClinic = $startClinic->addMinutes($b->service->duration_minutes);

        // Emit naive (no-offset) clinic-local strings. FullCalendar treats
        // these as already-in-zone, so it skips its own conversion — which
        // would otherwise need a tz plugin to handle named zones correctly.
        $iso = fn ($dt) => $dt->format('Y-m-d\TH:i:s');

        return [
            'id' => $b->id,
            'title' => $b->customer_name.' — '.$b->service->name,
            'start' => $iso($startClinic),
            'end' => $iso($endClinic),
            'url' => route('admin.bookings.show', $b),
            'backgroundColor' => $this->color($b->status),
            'borderColor' => $this->color($b->status),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $b->status->value,
                'statusLabel' => $b->status->label(),
                'reference' => $b->reference,
                'phone' => $b->customer_phone,
            ],
        ];
    }

    /** Brand-token colors — kept in sync with resources/css/app.css. */
    private function color(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Confirmed => '#1f8a4a',      // brand-green
            BookingStatus::PendingPayment => '#ef7a2a', // brand-orange
            BookingStatus::Completed => '#1f7a8c',      // brand-blue
            BookingStatus::Cancelled => '#b91c1c',      // red-700 (no brand red)
            BookingStatus::NoShow => '#5a6b75',         // brand-muted
            default => '#5a6b75',
        };
    }
}
