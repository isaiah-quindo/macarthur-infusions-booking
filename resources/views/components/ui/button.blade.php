@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';
    $variants = [
        'primary' => 'bg-brand-orange text-white shadow-lg shadow-brand-orange/20 hover:bg-brand-orange-soft focus:ring-brand-orange',
        'secondary' => 'bg-brand-teal text-brand-cream hover:bg-brand-teal-mid focus:ring-brand-teal',
        'ghost' => 'border border-transparent text-brand-teal hover:bg-brand-teal/10 hover:text-brand-teal-deep focus:bg-brand-teal/10 focus:text-brand-teal-deep focus:ring-brand-green',
        'outline' => 'border border-brand-teal bg-transparent text-brand-teal hover:border-brand-teal-deep hover:text-brand-teal-deep focus:border-brand-teal-deep focus:text-brand-teal-deep focus:ring-brand-teal',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-600',
    ];
    $classes = $base.' '.$variants[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
