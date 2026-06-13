@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-brand-green/30 bg-brand-green/5 text-brand-green-deep',
        'error' => 'border-red-300 bg-red-50 text-red-800',
        'warning' => 'border-brand-orange/30 bg-brand-orange/5 text-brand-orange',
        'info' => 'border-brand-blue/30 bg-brand-mist text-brand-teal',
    ];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 text-sm font-medium '.$styles[$type]]) }}>
    {{ $slot }}
</div>
