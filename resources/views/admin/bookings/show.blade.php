@extends('layouts.admin')

@section('title', 'Booking '.$booking->reference)

@section('content')
    <x-ui.button variant="ghost" class="cursor-pointer" :href="route('admin.dashboard', ['week' => $booking->startsAtClinic()->toDateString()])">
        <x-lucide-arrow-left class="size-4" />
        Bookings
    </x-ui.button>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold font-mono">{{ $booking->reference }}</h1>
        <x-ui.badge :status="$booking->status" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h2 class="text-lg font-semibold">Appointment</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-brand-muted">Service</dt><dd class="font-semibold">{{ $booking->service->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-brand-muted">When</dt><dd class="font-semibold">{{ $booking->startsAtClinic()->format('D j M Y, g:ia') }}–{{ $booking->startsAtClinic()->addMinutes($booking->service->duration_minutes)->format('g:ia') }}</dd></div>
                <div class="flex justify-between"><dt class="text-brand-muted">Price</dt><dd class="font-semibold">{{ $booking->service->priceFormatted() }}</dd></div>
                <div class="flex justify-between"><dt class="text-brand-muted">Payment</dt>
                    <dd class="font-semibold">
                        {{ $booking->payment_method->label() }}
                        @if ($booking->isPaid())
                            <span class="text-brand-green">· paid</span>
                        @elseif ($booking->payment_method === \App\Enums\PaymentMethod::InPerson)
                            <span class="text-brand-orange">· to collect</span>
                        @else
                            <span class="text-red-600">· unpaid</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-brand-muted">Customer</dt><dd class="font-semibold">{{ $booking->customer_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-brand-muted">Email</dt><dd><a class="font-semibold text-brand-green hover:underline" href="mailto:{{ $booking->customer_email }}">{{ $booking->customer_email }}</a></dd></div>
                <div class="flex justify-between"><dt class="text-brand-muted">Phone</dt><dd><a class="font-semibold text-brand-green hover:underline" href="tel:{{ preg_replace('/\s+/', '', $booking->customer_phone) }}">{{ $booking->customer_phone }}</a></dd></div>
                @if ($booking->notes)
                    <div><dt class="text-brand-muted">Notes</dt><dd class="mt-1 rounded-lg bg-brand-mist p-3">{{ $booking->notes }}</dd></div>
                @endif
            </dl>

            <h3 class="mt-6 text-sm font-semibold uppercase tracking-wider text-brand-muted">Payments</h3>
            <div class="mt-2 space-y-2 text-sm">
                @forelse ($booking->payments as $payment)
                    <div class="flex items-center justify-between rounded-lg border border-brand-border px-3 py-2">
                        <span>${{ number_format($payment->amount_cents / 100, 2) }} {{ $payment->currency }}
                            @if ($payment->square_payment_id)<span class="text-xs text-brand-muted">· {{ $payment->square_payment_id }}</span>@endif
                        </span>
                        <span class="text-xs font-bold uppercase {{ $payment->status === 'completed' ? 'text-brand-green' : ($payment->status === 'refunded' ? 'text-brand-blue' : 'text-red-600') }}">{{ $payment->status }}</span>
                    </div>
                @empty
                    <p class="text-brand-muted">No payment attempts.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-semibold">Actions</h2>

            @if (in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::PendingPayment]))
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                        <x-ui.button type="button" variant="secondary" data-hs-overlay="#complete-booking-modal">Mark completed</x-ui.button>
                        <x-ui.button type="button" variant="outline" data-hs-overlay="#no-show-modal">No-show</x-ui.button>
                    @endif
                    <x-ui.button type="button" variant="danger" data-hs-overlay="#cancel-booking-modal">Cancel booking</x-ui.button>
                </div>

                <h3 class="mt-7 text-sm font-semibold uppercase tracking-wider text-brand-muted">Reschedule</h3>
                <form method="post" action="{{ route('admin.bookings.update', $booking) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="reschedule">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Date</label>
                        <input type="date" name="date" required value="{{ $booking->startsAtClinic()->toDateString() }}"
                               class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-1.5">Time</label>
                        <input type="time" name="time" required value="{{ $booking->startsAtClinic()->format('H:i') }}" step="900"
                               class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                    </div>
                    <x-ui.button type="submit" variant="ghost">Move booking</x-ui.button>
                </form>
            @endif

            @php
                $canRecordInPerson = $booking->payment_method === \App\Enums\PaymentMethod::InPerson
                    && ! $booking->isPaid()
                    && in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Completed]);
            @endphp
            @if ($canRecordInPerson)
                <h3 class="mt-7 text-sm font-semibold uppercase tracking-wider text-brand-muted">Payment to collect</h3>
                <p class="mt-1 text-sm text-brand-muted">{{ $booking->service->priceFormatted() }} due at the appointment. Once collected:</p>
                <form method="post" action="{{ route('admin.bookings.update', $booking) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="record_in_person_payment">
                    <x-ui.button type="submit" variant="secondary">Record {{ $booking->service->priceFormatted() }} collected</x-ui.button>
                </form>
            @endif

            @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->payments->firstWhere('status', 'completed'))
                <x-ui.alert type="warning" class="mt-4">
                    A captured payment exists. Refund it in the
                    <a href="https://squareup.com/dashboard" target="_blank" class="underline font-bold">Square Dashboard</a>, then:
                </x-ui.alert>
                <form method="post" action="{{ route('admin.bookings.update', $booking) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="mark_refunded">
                    <x-ui.button type="submit" variant="secondary">Mark refunded</x-ui.button>
                </form>
            @endif

            @if (! in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::PendingPayment, \App\Enums\BookingStatus::Cancelled]) && ! $canRecordInPerson)
                <p class="mt-4 text-sm text-brand-muted">No actions available for this status.</p>
            @endif
        </x-ui.card>
    </div>

    {{-- Mark completed modal --}}
    <div id="complete-booking-modal"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
         role="dialog" tabindex="-1" aria-labelledby="complete-booking-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto">
            <div class="flex flex-col bg-white border border-brand-border rounded-2xl shadow-lg pointer-events-auto">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <h3 id="complete-booking-label" class="font-display text-lg font-semibold text-brand-teal">Mark as completed?</h3>
                    <button type="button"
                            class="cursor-pointer inline-flex size-8 items-center justify-center rounded-full text-brand-muted hover:bg-brand-mist"
                            data-hs-overlay="#complete-booking-modal" aria-label="Close">
                        <x-lucide-x class="size-4" />
                    </button>
                </div>

                <div class="px-5 py-5 text-sm text-brand-muted">
                    <p>Confirm that <strong class="font-mono text-brand-teal">{{ $booking->reference }}</strong> has finished. Status moves to <strong class="text-brand-teal">Completed</strong>.</p>
                </div>

                <form method="post" action="{{ route('admin.bookings.update', $booking) }}"
                      class="flex justify-end gap-2 border-t border-brand-border px-5 py-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="complete">
                    <x-ui.button type="button" variant="ghost" data-hs-overlay="#complete-booking-modal">Not yet</x-ui.button>
                    <x-ui.button type="submit" variant="secondary">Mark completed</x-ui.button>
                </form>
            </div>
        </div>
    </div>

    {{-- No-show modal --}}
    <div id="no-show-modal"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
         role="dialog" tabindex="-1" aria-labelledby="no-show-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto">
            <div class="flex flex-col bg-white border border-brand-border rounded-2xl shadow-lg pointer-events-auto">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <h3 id="no-show-label" class="font-display text-lg font-semibold text-brand-teal">Mark as no-show?</h3>
                    <button type="button"
                            class="cursor-pointer inline-flex size-8 items-center justify-center rounded-full text-brand-muted hover:bg-brand-mist"
                            data-hs-overlay="#no-show-modal" aria-label="Close">
                        <x-lucide-x class="size-4" />
                    </button>
                </div>

                <div class="px-5 py-5 text-sm text-brand-muted">
                    <p>The patient didn't attend <strong class="font-mono text-brand-teal">{{ $booking->reference }}</strong>. Status moves to <strong class="text-brand-teal">No-show</strong>.</p>
                </div>

                <form method="post" action="{{ route('admin.bookings.update', $booking) }}"
                      class="flex justify-end gap-2 border-t border-brand-border px-5 py-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="no_show">
                    <x-ui.button type="button" variant="ghost" data-hs-overlay="#no-show-modal">Cancel</x-ui.button>
                    <x-ui.button type="submit" variant="outline">Mark no-show</x-ui.button>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel booking modal --}}
    <div id="cancel-booking-modal"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
         role="dialog" tabindex="-1" aria-labelledby="cancel-booking-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto">
            <div class="flex flex-col bg-white border border-brand-border rounded-2xl shadow-lg pointer-events-auto">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <h3 id="cancel-booking-label" class="font-display text-lg font-semibold text-brand-teal">Cancel booking?</h3>
                    <button type="button"
                            class="cursor-pointer inline-flex size-8 items-center justify-center rounded-full text-brand-muted hover:bg-brand-mist"
                            data-hs-overlay="#cancel-booking-modal" aria-label="Close">
                        <x-lucide-x class="size-4" />
                    </button>
                </div>

                <div class="px-5 py-5 text-sm text-brand-muted">
                    <p>This will cancel <strong class="font-mono text-brand-teal">{{ $booking->reference }}</strong> and email the customer.</p>
                    @if ($booking->payments->firstWhere('status', 'completed'))
                        <p class="mt-3">A captured payment exists — refund it in the <a href="https://squareup.com/dashboard" target="_blank" class="text-brand-green hover:underline font-semibold">Square Dashboard</a> after cancelling, then return here to mark it refunded.</p>
                    @endif
                </div>

                <form method="post" action="{{ route('admin.bookings.update', $booking) }}"
                      class="flex justify-end gap-2 border-t border-brand-border px-5 py-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="cancel">
                    <x-ui.button type="button" variant="ghost" data-hs-overlay="#cancel-booking-modal">Keep booking</x-ui.button>
                    <x-ui.button type="submit" variant="danger">Cancel booking</x-ui.button>
                </form>
            </div>
        </div>
    </div>
@endsection
