@extends('layouts.admin')

@section('title', $past ? 'Past bookings' : 'Bookings')

@php
    use Carbon\CarbonImmutable;

    $dateHeading = function (string $date) use ($now) {
        $d = CarbonImmutable::parse($date, config('booking.clinic_timezone'));
        $rel = match (true) {
            $d->isSameDay($now->addDay()) => 'Tomorrow',
            $d->isSameDay($now) => 'Today',
            $d->lte($now->endOfWeek()) => $d->format('l'),
            default => $d->format('l'),
        };

        return $rel.' · '.$d->format('j F');
    };
@endphp

@section('content')
    @if ($past)
        {{-- Past bookings --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Past bookings</h1>
            <x-ui.button variant="ghost" class="cursor-pointer" :href="route('admin.dashboard')">&larr; Back to today</x-ui.button>
        </div>

        @forelse ($pastBookings as $date => $rows)
            <h2 class="mt-8 mb-3 text-sm font-semibold uppercase tracking-wider text-brand-muted">{{ $dateHeading($date) }}</h2>
            <div class="space-y-2.5">
                @foreach ($rows as $booking)
                    @include('admin.partials.booking-row')
                @endforeach
            </div>
        @empty
            <p class="mt-8 text-brand-muted">No past bookings.</p>
        @endforelse
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Bookings</h1>
                <p class="mt-1 text-sm text-brand-muted">{{ $now->format('l j F Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button variant="primary" class="cursor-pointer" data-hs-overlay="#create-booking-modal">
                    <x-lucide-plus class="size-4" />
                    Create booking
                </x-ui.button>
                <x-ui.button variant="ghost" class="cursor-pointer" :href="route('admin.dashboard', ['past' => 1])">Past bookings</x-ui.button>
            </div>
        </div>

        {{-- Today --}}
        <div class="mt-8 flex items-baseline gap-3">
            <h2 class="text-lg font-semibold">Today</h2>
            <span class="text-sm text-brand-muted">{{ $today->count() }} {{ Str::plural('appointment', $today->count()) }}</span>
        </div>
        <div class="mt-3 space-y-2.5">
            @forelse ($today as $booking)
                @include('admin.partials.booking-row')
            @empty
                <div class="rounded-xl border border-dashed border-brand-border bg-white/60 px-4 py-8 text-center text-sm text-brand-muted">
                    No appointments today.
                </div>
            @endforelse
        </div>

        {{-- Upcoming --}}
        <div class="mt-10 flex items-baseline gap-3">
            <h2 class="text-lg font-semibold">Upcoming</h2>
            <span class="text-sm text-brand-muted">{{ $upcoming->flatten()->count() }} {{ Str::plural('appointment', $upcoming->flatten()->count()) }}</span>
        </div>

        @forelse ($upcoming as $date => $rows)
            <h3 class="mt-6 mb-3 text-xs font-semibold uppercase tracking-wider text-brand-muted">{{ $dateHeading($date) }}</h3>
            <div class="space-y-2.5">
                @foreach ($rows as $booking)
                    @include('admin.partials.booking-row')
                @endforeach
            </div>
        @empty
            <div class="mt-3 rounded-xl border border-dashed border-brand-border bg-white/60 px-4 py-8 text-center text-sm text-brand-muted">
                Nothing booked ahead yet.
            </div>
        @endforelse
    @endif
@endsection
