@props(['status'])

@php
    $styles = [
        'pending_payment' => 'bg-brand-orange/10 text-brand-orange',
        'confirmed' => 'bg-brand-green/10 text-brand-green-deep',
        'completed' => 'bg-brand-blue/10 text-brand-blue',
        'cancelled' => 'bg-red-100 text-red-700',
        'abandoned' => 'bg-gray-100 text-gray-500',
        'no_show' => 'bg-gray-200 text-gray-600',
    ];
    $value = $status instanceof \App\Enums\BookingStatus ? $status : \App\Enums\BookingStatus::from($status);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$styles[$value->value]]) }}>
    {{ $value->label() }}
</span>
