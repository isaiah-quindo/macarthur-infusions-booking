@extends('layouts.app')

@section('title', 'Payment — '.$booking->reference)

@section('content')
<div class="mx-auto max-w-lg"
     x-data="payPage(@js($booking->hold_expires_at->timestamp))" x-init="tick()">

    <h1 class="text-3xl font-semibold">Secure payment</h1>
    <p class="mt-2 text-brand-muted">
        {{ $booking->service->name }} ·
        {{ $booking->startsAtClinic()->format('l j F, g:ia') }} ·
        <span class="font-semibold text-brand-green-deep">{{ $booking->service->priceFormatted() }}</span>
    </p>

    <x-ui.alert type="warning" class="mt-5">
        Your slot is held for <strong x-text="remaining"></strong>. Complete payment to confirm.
    </x-ui.alert>

    <x-ui.card class="mt-6">
        @if (! $squareConfigured)
            <x-ui.alert type="error">
                Online payments are not configured yet. Please contact us on
                <a class="underline" href="tel:{{ config('booking.clinic.phone') }}">{{ config('booking.clinic.phone') }}</a>
                to finish your booking.
            </x-ui.alert>
        @else
            <p class="text-sm text-brand-muted">
                Click below to continue to Square's secure payment page. Your card details never touch our site.
            </p>

            <form method="post" action="{{ route('booking.pay.redirect', $booking) }}" class="mt-5">
                @csrf
                <x-ui.button type="submit" variant="primary" class="w-full">
                    Continue to Square — Pay {{ $booking->service->priceFormatted() }}
                </x-ui.button>
            </form>

            <p class="mt-4 inline-flex w-full items-center justify-center gap-1.5 text-xs text-brand-muted">
                <x-lucide-lock class="size-3" />
                Processed securely by
                <img src="/square-icon.svg" alt="" class="size-3" aria-hidden="true">
                <span class="font-medium text-brand-teal">Square</span>.
            </p>
        @endif
    </x-ui.card>
</div>

@push('scripts')
<script>
function payPage(expiresAt) {
    return {
        remaining: '',
        tick() {
            const left = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
            const m = Math.floor(left / 60), s = left % 60;
            this.remaining = `${m}:${String(s).padStart(2, '0')} minutes`;
            if (left === 0) { window.location.reload(); return; }
            setTimeout(() => this.tick(), 1000);
        },
    };
}
</script>
@endpush
@endsection
