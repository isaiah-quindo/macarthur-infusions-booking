<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Book an Appointment') — {{ config('booking.clinic.name') }}</title>

    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">
    <header class="bg-brand-teal">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex items-center justify-between">
            <a href="{{ config('booking.clinic.website') }}" class="flex items-center gap-3">
                <img src="/logo.png" alt="" class="h-11 w-11 drop-shadow">
                <span class="font-display text-xl text-brand-cream">
                    Macarthur <span class="text-brand-orange">Infusions</span>
                </span>
            </a>
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-10">
            @if (session('status'))
                <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="error" class="mb-6">{{ session('error') }}</x-ui.alert>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="bg-brand-teal-deep text-brand-cream/70 text-sm">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-8 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <div>
                <div class="font-display text-brand-cream">{{ config('booking.clinic.name') }}</div>
                <div class="mt-1">{{ config('booking.clinic.address') }}</div>
            </div>
            <div class="sm:text-right">
                <div><a href="tel:1300205970" class="hover:text-brand-cream transition">{{ config('booking.clinic.phone') }}</a></div>
                <div class="mt-1"><a href="{{ config('booking.clinic.website') }}" class="hover:text-brand-cream transition">macarthurinfusions.com.au</a></div>
                <div class="mt-1"><a href="{{ route('legal.privacy') }}" class="hover:text-brand-cream transition">Privacy Policy</a></div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
