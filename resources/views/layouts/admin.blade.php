<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('booking.clinic.name') }}</title>
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400..700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-mist">
    @php
        $navLinks = [
            ['admin.dashboard', 'Bookings'],
            ['admin.calendar', 'Calendar'],
            ['admin.services.index', 'Services'],
            ['admin.availability.index', 'Availability'],
        ];
    @endphp

    <header class="bg-brand-teal" x-data="{ open: false }" @keydown.escape.window="open = false">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                <img src="/logo.png" alt="" class="h-8 w-8 shrink-0">
                <span class="font-display text-brand-cream truncate">
                    Macarthur <span class="text-brand-orange">Infusions</span>
                    <span class="ml-1 inline-flex items-center gap-x-1.5 rounded-full bg-brand-blue px-2.5 py-1 text-[11px] font-medium font-sans text-white sm:px-3 sm:py-1.5 sm:text-xs">Admin</span>
                </span>
            </a>

            {{-- Desktop nav (md and up) --}}
            <nav class="hidden md:flex items-center gap-1 text-sm">
                @foreach ($navLinks as [$route, $label])
                    <a href="{{ route($route) }}"
                       class="rounded-full px-3.5 py-1.5 transition {{ request()->routeIs($route) ? 'bg-white/15 text-white font-semibold' : 'text-brand-cream/75 hover:text-white' }}">{{ $label }}</a>
                @endforeach
                <form method="post" action="{{ route('logout') }}" class="ml-2">
                    @csrf
                    <button class="cursor-pointer inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-brand-cream/60 hover:text-white transition">
                        <x-lucide-log-out class="size-4" />
                        Log out
                    </button>
                </form>
            </nav>

            {{-- Hamburger (mobile only) --}}
            <button type="button" @click="open = !open"
                    :aria-expanded="open.toString()" aria-controls="admin-mobile-nav" aria-label="Toggle menu"
                    class="md:hidden cursor-pointer rounded-full p-2 text-brand-cream hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition">
                <x-lucide-menu x-show="!open" class="size-6" />
                <x-lucide-x x-show="open" x-cloak class="size-6" />
            </button>
        </div>

        {{-- Mobile dropdown panel --}}
        <div id="admin-mobile-nav" x-show="open" x-cloak x-transition.opacity
             @click.outside="open = false"
             class="md:hidden border-t border-white/10 bg-brand-teal">
            <nav class="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex flex-col gap-1 text-sm">
                @foreach ($navLinks as [$route, $label])
                    <a href="{{ route($route) }}"
                       class="rounded-lg px-3.5 py-2.5 transition {{ request()->routeIs($route) ? 'bg-white/15 text-white font-semibold' : 'text-brand-cream/85 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
                @endforeach
                <form method="post" action="{{ route('logout') }}" class="mt-1 border-t border-white/10 pt-2">
                    @csrf
                    <button class="cursor-pointer inline-flex w-full items-center gap-2 rounded-lg px-3.5 py-2.5 text-brand-cream/70 hover:bg-white/5 hover:text-white transition">
                        <x-lucide-log-out class="size-4" />
                        Log out
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 sm:px-6 py-8">
        @if (session('status'))
            <x-ui.alert type="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif
        @if (session('error'))
            <x-ui.alert type="error" class="mb-6">{{ session('error') }}</x-ui.alert>
        @endif

        @yield('content')
    </main>

    @include('admin.partials.create-booking-modal')

    @stack('scripts')
</body>
</html>
