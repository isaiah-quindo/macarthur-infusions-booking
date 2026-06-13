@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
    <div class="mx-auto max-w-sm">
        <x-ui.card>
            <h1 class="text-2xl font-semibold">Forgot your password?</h1>
            <p class="mt-2 text-sm text-brand-muted">
                Enter your email and we'll send you a link to reset it.
            </p>

            <form method="post" action="{{ route('password.email') }}" class="mt-5 space-y-4">
                @csrf
                <x-ui.input name="email" type="email" label="Email" required value="{{ old('email') }}" />
                <x-ui.button type="submit" variant="primary" class="w-full cursor-pointer">Email me a reset link</x-ui.button>
            </form>

            <p class="mt-5 text-center text-sm">
                <a href="{{ route('login') }}" class="text-brand-blue hover:underline">← Back to login</a>
            </p>
        </x-ui.card>
    </div>
@endsection
