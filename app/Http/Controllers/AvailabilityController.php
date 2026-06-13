<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function __invoke(Request $request, Service $service)
    {
        abort_unless($service->is_active, 404);

        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $tz = $this->availability->clinicTimezone();
        $from = CarbonImmutable::parse($request->input('from'), $tz);
        $to = CarbonImmutable::parse($request->input('to'), $tz);

        if ($from->diffInDays($to) > 31) {
            abort(422, 'Range too large.');
        }

        return response()->json([
            'timezone' => $tz,
            'days' => $this->availability->slotsForRange($service, $from, $to),
        ]);
    }
}
