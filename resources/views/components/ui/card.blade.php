@props(['padding' => 'p-6 sm:p-8'])

<div {{ $attributes->merge(['class' => "rounded-2xl border border-brand-border bg-white shadow-sm $padding"]) }}>
    {{ $slot }}
</div>
