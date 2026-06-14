@extends('layouts.admin')

@section('title', 'Availability')

@php $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; @endphp

@section('content')
    <h1 class="text-2xl font-semibold">Availability</h1>

    @php
        $dayShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    @endphp

    {{-- Clinic settings --}}
    <x-ui.card class="mt-6">
        <h2 class="text-lg font-semibold">Clinic settings</h2>
        <form method="post" action="{{ route('admin.availability.settings') }}" class="mt-4 flex flex-wrap items-end gap-4">
            @csrf
            <div class="min-w-56 flex-1">
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Concurrent capacity</label>
                <input type="number" name="concurrent_capacity" min="1" max="50" required
                       value="{{ old('concurrent_capacity', $settings->concurrent_capacity) }}"
                       class="w-28 rounded-lg border border-brand-border px-3 py-2 text-sm">
                <p class="mt-1.5 text-xs text-brand-muted">
                    How many patients can be in treatment at the same time.
                </p>
            </div>
            <div class="min-w-56 flex-1">
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Booking window (days ahead)</label>
                <input type="number" name="max_advance_days" min="1" max="365" required
                       value="{{ old('max_advance_days', $settings->max_advance_days) }}"
                       class="w-28 rounded-lg border border-brand-border px-3 py-2 text-sm">
                <p class="mt-1.5 text-xs text-brand-muted">
                    How far ahead patients can book. {{ (int) old('max_advance_days', $settings->max_advance_days) }} days ≈ {{ number_format(((int) old('max_advance_days', $settings->max_advance_days)) / 30, 1) }} months.
                </p>
            </div>
            <x-ui.button type="submit" variant="secondary" class="cursor-pointer">Save</x-ui.button>
        </form>
    </x-ui.card>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="text-lg font-semibold">Weekly opening hours</h2>
            <div class="mt-4 space-y-2">
                @forelse ($rules as $rule)
                    <div class="flex items-center justify-between rounded-lg border border-brand-border px-3.5 py-2.5 text-sm">
                        <span><strong>{{ $dayNames[$rule->day_of_week] }}</strong> · {{ substr($rule->start_time, 0, 5) }}–{{ substr($rule->end_time, 0, 5) }}</span>
                        <form method="post" action="{{ route('admin.availability.rules.destroy', $rule) }}"
                              onsubmit="return confirm('Remove these hours?')">
                            @csrf @method('DELETE')
                            <button class="cursor-pointer text-xs font-semibold text-red-600 hover:underline">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-brand-muted">No opening hours — nothing is bookable.</p>
                @endforelse
            </div>

            <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-brand-muted">Add hours</h3>
            @php
                $dayChevron = '<svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
                $dayCheck = '<svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
                $daySelectConfig = [
                    'placeholder' => 'Select a day…',
                    'toggleTag' => '<button type="button" aria-expanded="false"></button>',
                    'toggleClasses' => 'cursor-pointer hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative flex w-44 items-center justify-between gap-2 rounded-lg border border-brand-border bg-white px-3.5 py-2 text-start text-sm text-brand-teal-deep focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none',
                    'dropdownClasses' => 'mt-2 z-[90] w-44 max-h-72 p-1 space-y-0.5 bg-white border border-brand-border rounded-lg overflow-hidden overflow-y-auto shadow-lg',
                    'optionClasses' => 'flex items-center justify-between gap-2 py-2 px-3 w-full text-sm text-brand-teal-deep cursor-pointer rounded-lg hover:bg-brand-mist focus:outline-none focus:bg-brand-mist hs-selected:bg-brand-mist',
                    'optionTemplate' => '<div class="flex w-full items-center justify-between"><span data-title></span><span class="hidden hs-selected:block text-brand-green">'.$dayCheck.'</span></div>',
                    'extraMarkup' => '<div class="absolute top-1/2 end-3 -translate-y-1/2 text-brand-muted">'.$dayChevron.'</div>',
                ];
            @endphp
            <form method="post" action="{{ route('admin.availability.rules.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label for="day_of_week" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Day</label>
                    <select id="day_of_week" name="day_of_week" required data-hs-select='@json($daySelectConfig)' class="hidden">
                        @foreach ($dayNames as $i => $name)
                            <option value="{{ $i }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">From</label>
                    <input type="time" name="start_time" required value="09:00" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">To</label>
                    <input type="time" name="end_time" required value="17:00" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                </div>
                <x-ui.button type="submit" variant="secondary" class="cursor-pointer">
                    <x-lucide-plus class="size-4" />
                    Add
                </x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Blocked time</h2>
            <p class="mt-1 text-sm text-brand-muted">Holidays, sick days, lunch breaks — anything here is unbookable.</p>
            <div class="mt-4 space-y-2">
                @forelse ($blocks as $block)
                    <div class="flex items-center justify-between rounded-lg border border-brand-border px-3.5 py-2.5 text-sm">
                        <span>
                            <strong>{{ $block->starts_at->setTimezone(config('booking.clinic_timezone'))->format('D j M, g:ia') }}</strong>
                            → {{ $block->ends_at->setTimezone(config('booking.clinic_timezone'))->format('D j M, g:ia') }}
                            @if ($block->reason)<span class="text-brand-muted">· {{ $block->reason }}</span>@endif
                        </span>
                        <form method="post" action="{{ route('admin.availability.blocks.destroy', $block) }}">
                            @csrf @method('DELETE')
                            <button class="cursor-pointer text-xs font-semibold text-red-600 hover:underline">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-brand-muted">No upcoming blocks.</p>
                @endforelse
            </div>

            <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-brand-muted">Block time out</h3>
            <form method="post" action="{{ route('admin.availability.blocks.store') }}" class="mt-3 space-y-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">From</label>
                        <input type="datetime-local" name="starts_at" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">To</label>
                        <input type="datetime-local" name="ends_at" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Reason (optional)</label>
                        <input type="text" name="reason" placeholder="Holiday, training, lunch…" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                    </div>
                    <x-ui.button type="submit" variant="secondary" class="cursor-pointer">Block</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    {{-- Recurring blocks (full width, below the two-column row) --}}
    <x-ui.card class="mt-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">Recurring blocks</h2>
                <p class="mt-1 text-sm text-brand-muted">Weekly unbookable windows — lunch breaks, admin time, weekly closures.</p>
            </div>
            <form method="post" action="{{ route('admin.availability.recurring-blocks.lunch-preset') }}"
                  class="flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-brand-muted mb-1">Lunch from</label>
                    <input type="time" name="start_time" value="12:00" required class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold uppercase tracking-wider text-brand-muted mb-1">Lunch to</label>
                    <input type="time" name="end_time" value="13:00" required class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                </div>
                <x-ui.button type="submit" variant="outline" class="cursor-pointer">
                    <x-lucide-utensils class="size-4" />
                    Add lunch (Mon–Fri)
                </x-ui.button>
            </form>
        </div>

        <div class="mt-4 space-y-2">
            @forelse ($recurringBlocks as $rb)
                <div class="flex items-center justify-between rounded-lg border border-brand-border px-3.5 py-2.5 text-sm">
                    <span>
                        <strong>{{ $dayNames[$rb->day_of_week] }}</strong>
                        · {{ substr($rb->start_time, 0, 5) }}–{{ substr($rb->end_time, 0, 5) }}
                        @if ($rb->reason)<span class="text-brand-muted">· {{ $rb->reason }}</span>@endif
                    </span>
                    <form method="post" action="{{ route('admin.availability.recurring-blocks.destroy', $rb) }}"
                          onsubmit="return confirm('Remove this recurring block?')">
                        @csrf @method('DELETE')
                        <button class="cursor-pointer text-xs font-semibold text-red-600 hover:underline">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-brand-muted">No recurring blocks. Use "Add lunch" above, or build a custom one below.</p>
            @endforelse
        </div>

        <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-brand-muted">Add a custom recurring block</h3>
        <form method="post" action="{{ route('admin.availability.recurring-blocks.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Day</label>
                <select name="day_of_week" required class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                    @foreach ($dayNames as $i => $name)
                        <option value="{{ $i }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">From</label>
                <input type="time" name="start_time" required value="12:00" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">To</label>
                <input type="time" name="end_time" required value="13:00" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <div class="min-w-44 flex-1">
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Reason (optional)</label>
                <input type="text" name="reason" placeholder="Lunch, admin time…" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <x-ui.button type="submit" variant="secondary" class="cursor-pointer">
                <x-lucide-plus class="size-4" />
                Add
            </x-ui.button>
        </form>
    </x-ui.card>
@endsection
