<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\ClinicSetting;
use App\Models\RecurringBlock;
use App\Models\TimeBlock;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index()
    {
        return view('admin.availability.index', [
            'rules' => AvailabilityRule::orderBy('day_of_week')->orderBy('start_time')->get(),
            'blocks' => TimeBlock::where('ends_at', '>', now())->orderBy('starts_at')->get(),
            'recurringBlocks' => RecurringBlock::orderBy('day_of_week')->orderBy('start_time')->get(),
            'settings' => ClinicSetting::current(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'concurrent_capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        ClinicSetting::current()->update($data);

        return back()->with('status', 'Clinic settings updated.');
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        AvailabilityRule::create($data);

        return back()->with('status', 'Opening hours added.');
    }

    public function destroyRule(AvailabilityRule $rule)
    {
        $rule->delete();

        return back()->with('status', 'Opening hours removed.');
    }

    public function storeBlock(Request $request)
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        $tz = config('booking.clinic_timezone');

        TimeBlock::create([
            'starts_at' => CarbonImmutable::parse($data['starts_at'], $tz)->utc(),
            'ends_at' => CarbonImmutable::parse($data['ends_at'], $tz)->utc(),
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Time blocked out.');
    }

    public function destroyBlock(TimeBlock $block)
    {
        $block->delete();

        return back()->with('status', 'Time block removed.');
    }

    public function storeRecurringBlock(Request $request)
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        RecurringBlock::create($data);

        return back()->with('status', 'Recurring block added.');
    }

    public function destroyRecurringBlock(RecurringBlock $recurringBlock)
    {
        $recurringBlock->delete();

        return back()->with('status', 'Recurring block removed.');
    }

    /** Preset: add a Mon–Fri lunch break with one tap. */
    public function addLunchPreset(Request $request)
    {
        $data = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        // Mon (1) through Fri (5). Skip days that already have an overlapping
        // recurring block so re-adding the preset is idempotent.
        foreach (range(1, 5) as $day) {
            $exists = RecurringBlock::where('day_of_week', $day)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if (! $exists) {
                RecurringBlock::create([
                    'day_of_week' => $day,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'reason' => 'Lunch break',
                ]);
            }
        }

        return back()->with('status', 'Lunch break added Monday–Friday.');
    }
}
