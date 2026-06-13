@extends('layouts.app')

@section('title', 'Choose a new password')

@section('content')
    <div class="mx-auto max-w-sm">
        <x-ui.card>
            <h1 class="text-2xl font-semibold">Choose a new password</h1>
            <p class="mt-2 text-sm text-brand-muted">
                Pick something at least 8 characters long.
            </p>

            <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-ui.input name="email" type="email" label="Email" required value="{{ old('email', $email) }}" />
                <x-ui.input name="password" type="password" label="New password" required />
                <x-ui.input name="password_confirmation" type="password" label="Confirm new password" required />
                <x-ui.button type="submit" variant="primary" class="w-full cursor-pointer">Update password</x-ui.button>
            </form>
        </x-ui.card>
    </div>
@endsection
