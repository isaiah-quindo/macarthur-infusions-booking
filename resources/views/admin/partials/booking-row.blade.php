@php($cancelled = $booking->status === \App\Enums\BookingStatus::Cancelled)
<a href="{{ route('admin.bookings.show', $booking) }}"
   class="group flex items-center gap-6 rounded-xl border border-brand-border bg-white px-5 py-5 transition hover:border-brand-green/40 hover:shadow-sm {{ $cancelled ? 'opacity-60' : '' }}">

    <div class="-my-5 flex shrink-0 flex-col items-center justify-center self-stretch border-r border-brand-border py-5 pr-4 leading-none">
        <span class="text-lg font-bold text-brand-teal">{{ $booking->startsAtClinic()->format('g:i') }}</span>
        <span class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-muted">{{ $booking->startsAtClinic()->format('a') }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <div class="truncate font-semibold text-brand-teal {{ $cancelled ? 'line-through' : '' }}">{{ $booking->customer_name }}</div>
        <div class="truncate text-sm text-brand-muted">
            {{ $booking->service->name }}
            <span class="text-brand-muted/60">· {{ $booking->service->duration_minutes }} min · {{ $booking->service->priceFormatted() }}</span>
        </div>
    </div>

    <div class="hidden shrink-0 items-center gap-2 sm:flex">
        @if ($booking->isPaid())
            <span class="text-xs font-bold text-brand-green">Paid</span>
        @elseif ($booking->payment_method === \App\Enums\PaymentMethod::InPerson && ! $cancelled)
            <span class="text-xs font-bold text-brand-orange">Collect {{ $booking->service->priceFormatted() }}</span>
        @endif
        <x-ui.badge :status="$booking->status" />
    </div>

    <x-lucide-chevron-right class="size-4 shrink-0 text-brand-muted transition group-hover:translate-x-0.5 group-hover:text-brand-green" />
</a>
