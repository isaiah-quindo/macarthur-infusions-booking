@props(['label' => null, 'name', 'type' => 'text', 'required' => false])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">
            {{ $label }}@if ($required)<span class="text-brand-orange"> *</span>@endif
        </label>
    @endif
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" @required($required)
            {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-sm text-brand-teal-deep shadow-xs placeholder:text-brand-muted/60 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none', 'rows' => 4]) }}>{{ old($name) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @required($required) value="{{ old($name) }}"
            {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-sm text-brand-teal-deep shadow-xs placeholder:text-brand-muted/60 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none']) }}>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
