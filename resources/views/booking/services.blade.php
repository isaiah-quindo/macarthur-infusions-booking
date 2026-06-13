@extends('layouts.app')

@section('title', 'Choose a Service')

@section('content')
<div x-data="{ active: 'All' }">
    <div class="max-w-2xl">
        <p class="text-xs font-semibold uppercase tracking-widest text-brand-orange">Online Booking</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-semibold">Choose your <em class="text-brand-green not-italic font-display italic">treatment.</em></h1>
        <p class="mt-3 text-brand-muted">Pick a service to see available times. Payment is taken securely when you book, and your appointment is confirmed instantly.</p>
    </div>

    {{-- Category tabs (sticky, scrollable on mobile) --}}
    <div class="sticky top-0 z-20 -mx-4 sm:-mx-6 mt-8 bg-brand-cream/95 px-4 sm:px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-brand-cream/80">
        <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @php
                $allCategories = collect(['All'])->merge($categories);
            @endphp
            @foreach ($allCategories as $cat)
                <button type="button"
                        @click="active = @js($cat); $el.scrollIntoView({behavior:'smooth', block:'nearest', inline:'center'})"
                        :class="active === @js($cat)
                            ? 'bg-brand-teal text-white border-brand-teal'
                            : 'bg-white text-brand-teal hover:bg-brand-mist border-brand-border'"
                        class="shrink-0 cursor-pointer rounded-full border px-4 py-1.5 text-sm font-medium transition">
                    {{ $cat }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Service grid --}}
    <div class="mt-6 grid gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($services as $service)
            <a href="{{ route('booking.create', $service) }}"
               x-show="active === 'All' || active === @js($service->category)"
               x-transition.opacity
               class="group flex flex-col overflow-hidden rounded-2xl border border-brand-border bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-brand-green/40 hover:shadow-lg">
                {{-- Image / gradient header --}}
                <div class="relative aspect-[4/3] w-full overflow-hidden bg-gradient-to-br from-brand-teal via-brand-teal-mid to-brand-green">
                    @if ($service->imageUrl())
                        <img src="{{ $service->imageUrl() }}" alt="{{ $service->name }}"
                             class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></div>
                    @else
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_25%_20%,rgba(255,255,255,0.18),transparent_55%)]"></div>
                    @endif

                    {{-- Category chip --}}
                    <span class="absolute left-3 top-3 inline-flex items-center gap-x-1.5 rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-medium text-white backdrop-blur-sm">
                        <x-lucide-sparkles class="size-3" />
                        {{ $service->category }}
                    </span>

                    {{-- Price (prominent, bottom-right) --}}
                    <span class="absolute bottom-3 right-3 rounded-lg bg-white/20 px-2.5 py-1 text-base font-bold tracking-tight text-white tabular-nums backdrop-blur-sm">
                        {{ $service->priceFormatted() }}
                    </span>
                </div>

                {{-- Body --}}
                <div class="flex flex-1 flex-col p-5">
                    <h3 class="text-lg font-semibold text-brand-teal group-hover:text-brand-green transition">{{ $service->name }}</h3>
                    <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-brand-muted">
                        <x-lucide-clock class="size-3.5" />
                        {{ $service->duration_minutes }} minutes
                    </p>

                    <p class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-orange">
                        Book now
                        <x-lucide-arrow-right class="size-3.5 transition group-hover:translate-x-0.5" stroke-width="2.5" />
                    </p>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Empty state per-category (rendered server-side, hidden client-side unless we know) --}}
    @if ($services->isEmpty())
        <p class="mt-10 text-center text-brand-muted">No services are available right now. Please check back soon, or call us on {{ config('booking.clinic.phone') }}.</p>
    @endif
</div>
@endsection
