@extends('layouts.app')

@section('title', 'Booking '.$booking->reference)

@section('content')
    <div class="mx-auto max-w-xl">
        @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
            <div class="text-center">
                <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-green/10">
                    <x-lucide-check class="size-7 text-brand-green" stroke-width="2.5" />
                </div>
                <h1 class="mt-4 text-3xl font-semibold">You're booked!</h1>
                <p class="mt-2 text-brand-muted">A confirmation email is on its way to {{ $booking->customer_email }}.</p>
            </div>
        @else
            <h1 class="text-3xl font-semibold">Your booking</h1>
        @endif

        <x-ui.card class="mt-8">
            <div class="flex items-center justify-between">
                <span class="font-mono text-sm font-bold text-brand-teal">{{ $booking->reference }}</span>
                <x-ui.badge :status="$booking->status" />
            </div>

            <dl class="mt-6 space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-brand-muted">Service</dt>
                    <dd class="font-semibold text-right">{{ $booking->service->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-brand-muted">When</dt>
                    <dd class="font-semibold text-right">
                        {{ $booking->startsAtClinic()->format('l j F Y') }}<br>
                        {{ $booking->startsAtClinic()->format('g:ia') }}–{{ $booking->startsAtClinic()->addMinutes($booking->service->duration_minutes)->format('g:ia') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-brand-muted">Where</dt>
                    <dd class="font-semibold text-right">{{ config('booking.clinic.address') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-brand-muted">Price</dt>
                    <dd class="font-semibold text-right">{{ $booking->service->priceFormatted() }}
                        @if ($booking->payments->firstWhere('status', \App\Models\Payment::STATUS_COMPLETED))
                            <span class="text-brand-green"> · paid</span>
                        @elseif ($booking->payment_method === \App\Enums\PaymentMethod::InPerson)
                            <span class="text-brand-orange"> · due at appointment</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-brand-muted">Name</dt>
                    <dd class="font-semibold text-right">{{ $booking->customer_name }}</dd>
                </div>
            </dl>

            @if ($booking->status === \App\Enums\BookingStatus::PendingPayment && ! $booking->holdHasExpired())
                <div class="mt-6">
                    <x-ui.button :href="route('booking.pay', $booking)" variant="primary" class="w-full">Complete payment</x-ui.button>
                </div>
            @endif
        </x-ui.card>

        <p class="mt-6 text-sm text-brand-muted text-center">
            Need to change or cancel? Call us on
            <a href="tel:1300205970" class="font-semibold text-brand-teal hover:text-brand-green transition">{{ config('booking.clinic.phone') }}</a>.
            {{ config('booking.clinic.cancellation_policy') }}
        </p>
    </div>
@endsection
