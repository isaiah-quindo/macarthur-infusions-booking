@extends('layouts.app')

@section('title', 'Verify your booking')

@section('content')
    <div class="mx-auto max-w-md">
        <x-ui.card>
            <h1 class="text-2xl font-semibold">Booking {{ $booking->reference }}</h1>
            <p class="mt-2 text-sm text-brand-muted">To protect your details, please confirm the email address used for this booking.</p>

            <form method="post" action="{{ route('booking.verify', $booking) }}" class="mt-5 space-y-4">
                @csrf
                <x-ui.input name="email" type="email" label="Email" required placeholder="you@email.com" />
                <x-ui.button type="submit" variant="primary" class="w-full">View my booking</x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
