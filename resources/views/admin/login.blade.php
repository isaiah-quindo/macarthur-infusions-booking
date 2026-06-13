@extends('layouts.app')

@section('title', 'Admin login')

@section('content')
    <div class="mx-auto max-w-sm">
        <x-ui.card>
            <h1 class="text-2xl font-semibold">Clinic login</h1>
            <form method="post" action="{{ route('login') }}" class="mt-5 space-y-4">
                @csrf
                <x-ui.input name="email" type="email" label="Email" required />
                <x-ui.input name="password" type="password" label="Password" required />
                <x-ui.button type="submit" variant="primary" class="w-full cursor-pointer">Log in</x-ui.button>
            </form>

            <p class="mt-5 text-center text-sm">
                <a href="{{ route('password.request') }}" class="text-brand-blue hover:underline">Forgot your password?</a>
            </p>
        </x-ui.card>
    </div>
@endsection
