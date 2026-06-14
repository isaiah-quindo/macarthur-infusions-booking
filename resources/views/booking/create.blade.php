@extends('layouts.app')

@section('title', 'Book '.$service->name)

@section('content')
<div x-data="slotPicker()" x-init="init()">
    <x-ui.button variant="ghost" :href="route('booking.services')">
        <x-lucide-arrow-left class="size-4" />
        All services
    </x-ui.button>

    <div class="mt-6 grid gap-8 lg:grid-cols-5">
        {{-- LEFT: product detail column --}}
        <div class="space-y-10 lg:col-span-3">
            {{-- Hero --}}
            <div class="relative aspect-[16/9] w-full overflow-hidden rounded-2xl bg-gradient-to-br from-brand-teal via-brand-teal-mid to-brand-green">
                @if ($service->imageUrl())
                    <img src="{{ $service->imageUrl() }}" alt="{{ $service->name }}" class="absolute inset-0 size-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                @else
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.15),transparent_50%)]"></div>
                @endif
                <div class="absolute bottom-6 left-6 right-6">
                    <span class="inline-flex items-center gap-x-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white backdrop-blur">
                        <x-lucide-sparkles class="size-3.5" />
                        {{ $service->category }}
                    </span>
                    <div class="mt-3 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
                        <h1 class="text-4xl font-semibold text-white drop-shadow-sm">{{ $service->name }}</h1>
                        <p class="rounded-xl bg-white/15 px-3 py-1.5 text-3xl font-bold tracking-tight text-white tabular-nums backdrop-blur-sm drop-shadow-sm sm:text-4xl">
                            {{ $service->priceFormatted() }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Trust badges --}}
            <div class="grid gap-4 border-y border-brand-border py-5 text-sm sm:grid-cols-3">
                <div class="flex items-center gap-3">
                    <x-lucide-shield-check class="size-5 shrink-0 text-brand-green" />
                    <div>
                        <p class="font-semibold text-brand-teal">Registered nurses</p>
                        <p class="text-xs text-brand-muted">Australian-trained</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-lucide-sparkles class="size-5 shrink-0 text-brand-green" />
                    <div>
                        <p class="font-semibold text-brand-teal">Sterile clinic</p>
                        <p class="text-xs text-brand-muted">Hospital-grade standards</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <x-lucide-clock class="size-5 shrink-0 text-brand-green" />
                    <div>
                        <p class="font-semibold text-brand-teal">{{ $service->duration_minutes }} minutes</p>
                        <p class="text-xs text-brand-muted">Total session time</p>
                    </div>
                </div>
            </div>

            @if ($service->description)
                {{-- About --}}
                <div>
                    <h2 class="text-xl font-semibold">About this treatment</h2>
                    <div class="mt-3 space-y-3 leading-relaxed text-brand-muted">
                        @foreach (preg_split('/\R{2,}/', trim($service->description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (count($service->included ?? []))
                {{-- What's included --}}
                <div>
                    <h2 class="text-xl font-semibold">What's included</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($service->included as $item)
                            <li class="flex items-start gap-3">
                                <x-lucide-check class="mt-0.5 size-5 shrink-0 text-brand-green" />
                                <span class="text-brand-muted">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($service->benefits ?? []))
                {{-- Benefits --}}
                <div>
                    <h2 class="text-xl font-semibold">Benefits</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($service->benefits as $benefit)
                            <li class="flex items-start gap-3">
                                <x-lucide-check class="mt-0.5 size-5 shrink-0 text-brand-green" />
                                <span class="text-brand-muted">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($service->faqs ?? []))
                {{-- FAQ --}}
                <div>
                    <h2 class="text-xl font-semibold">Frequently asked questions</h2>
                    <div class="mt-4 divide-y divide-brand-border overflow-hidden rounded-xl border border-brand-border bg-white">
                        @foreach ($service->faqs as $faq)
                            <details class="group">
                                <summary class="flex cursor-pointer items-center justify-between gap-4 p-4 font-semibold text-brand-teal">
                                    {{ $faq['question'] ?? '' }}
                                    <x-lucide-chevron-down class="size-4 shrink-0 transition group-open:rotate-180" />
                                </summary>
                                <p class="px-4 pb-4 text-sm text-brand-muted">{{ $faq['answer'] ?? '' }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT: sticky booking buy-box --}}
        <div class="lg:col-span-2">
            <div class="lg:sticky lg:top-6">
                <x-ui.card>
                    <h2 class="mb-6 text-xl font-semibold text-brand-teal">Book your appointment</h2>

                    {{-- Stepper indicator --}}
                    <ol class="flex items-center gap-3">
                        <li class="flex items-center gap-2">
                            <div :class="step > 1 ? 'bg-brand-green text-white' : 'bg-brand-teal text-white'"
                                 class="flex size-8 items-center justify-center rounded-full text-sm font-semibold transition">
                                <span x-show="step === 1">1</span>
                                <template x-if="step > 1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </template>
                            </div>
                            <span :class="step === 1 ? 'text-brand-teal' : 'text-brand-muted'" class="text-sm font-semibold transition">Time</span>
                        </li>
                        <div class="h-px flex-1 bg-brand-border"></div>
                        <li class="flex items-center gap-2">
                            <div :class="step === 2 ? 'bg-brand-teal text-white' : 'border-2 border-brand-border bg-white text-brand-muted'"
                                 class="flex size-8 items-center justify-center rounded-full text-sm font-semibold transition">2</div>
                            <span :class="step === 2 ? 'text-brand-teal' : 'text-brand-muted'" class="text-sm font-semibold transition">Details</span>
                        </li>
                    </ol>

                    {{-- Step 1: pick a day + time --}}
                    <div x-show="step === 1" class="mt-6">
                        <h2 class="text-lg font-semibold">Choose a time</h2>

                        <div class="mt-4 flex items-center justify-between">
                            <button type="button" @click="prevWeek()" :disabled="!canGoBack()"
                                    class="cursor-pointer rounded-full border border-brand-border p-2 hover:border-brand-green/40 disabled:opacity-30">
                                <x-lucide-chevron-left class="size-4" />
                            </button>
                            <span class="text-sm font-semibold text-brand-teal" x-text="rangeLabel"></span>
                            <button type="button" @click="nextWeek()"
                                    class="cursor-pointer rounded-full border border-brand-border p-2 hover:border-brand-green/40">
                                <x-lucide-chevron-right class="size-4" />
                            </button>
                        </div>

                        <div class="mt-4 grid grid-cols-7 gap-1.5" x-show="!loading">
                            <template x-for="day in days" :key="day.date">
                                <button type="button"
                                        @click="day.slots.length && selectDay(day)"
                                        :disabled="!day.slots.length"
                                        :class="selectedDate === day.date
                                            ? 'bg-brand-teal text-white'
                                            : !day.slots.length
                                                ? 'bg-brand-mist text-brand-muted/40 cursor-not-allowed'
                                                : day.hasFree
                                                    ? 'bg-white border border-brand-border hover:border-brand-green/50 text-brand-teal cursor-pointer'
                                                    : 'bg-white border border-brand-border text-brand-muted/60'"
                                        class="rounded-lg py-2 text-center transition">
                                    <span class="block text-[10px] font-semibold uppercase" x-text="day.weekday"></span>
                                    <span class="block text-sm font-bold" x-text="day.dayNum"></span>
                                </button>
                            </template>
                        </div>
                        <p class="mt-4 text-sm text-brand-muted text-center" x-show="loading">Loading available times…</p>

                        <template x-if="selectedDate">
                            <div class="mt-6">
                                <p class="text-xs font-semibold uppercase tracking-wider text-brand-muted mb-3" x-text="'Times — ' + selectedDayLabel"></p>
                                <div class="grid grid-cols-4 gap-2">
                                    <template x-for="slot in selectedSlots" :key="slot.time">
                                        <button type="button"
                                                @click="slot.available && (selectedTime = slot.time)"
                                                :disabled="!slot.available"
                                                :title="slot.available ? '' : 'Already booked'"
                                                :class="!slot.available
                                                    ? 'bg-brand-mist border-brand-border text-brand-muted/40 line-through cursor-not-allowed'
                                                    : selectedTime === slot.time
                                                        ? 'bg-brand-orange text-white border-brand-orange cursor-pointer'
                                                        : 'bg-white border-brand-border hover:border-brand-orange/50 text-brand-teal cursor-pointer'"
                                                class="rounded-lg border py-2 text-sm font-semibold transition" x-text="slot.time"></button>
                                    </template>
                                </div>
                                <p class="mt-3 text-xs text-brand-muted" x-show="selectedSlots.length && !selectedSlots.some(s => s.available)" x-cloak>
                                    Every time this day is already booked — please choose another day.
                                </p>
                            </div>
                        </template>

                        <x-ui.button type="button" variant="primary" class="mt-6 w-full"
                                     @click="step = 2"
                                     x-bind:disabled="!selectedDate || !selectedTime">
                            Next
                            <x-lucide-arrow-right class="size-4" />
                        </x-ui.button>
                    </div>

                    {{-- Step 2: details --}}
                    <div x-show="step === 2" x-cloak class="mt-6">
                        <button type="button" @click="step = 1" class="cursor-pointer inline-flex items-center gap-1 text-sm text-brand-muted hover:text-brand-teal">
                            <x-lucide-arrow-left class="size-4" />
                            Back
                        </button>
                        <h2 class="mt-3 text-lg font-semibold">Your details</h2>

                        <form method="post" action="{{ route('booking.store', $service) }}" class="mt-4 flex flex-col space-y-4">
                            @csrf
                            <input type="hidden" name="date" :value="selectedDate" value="{{ old('date') }}">
                            <input type="hidden" name="time" :value="selectedTime" value="{{ old('time') }}">
                            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden">

                            <div x-show="selectedDate && selectedTime" x-cloak>
                                <x-ui.alert type="info">
                                    <span x-text="'Booking for ' + selectedDayLabel + ' at ' + selectedTime"></span>
                                    — {{ $service->name }}, {{ $service->priceFormatted() }}
                                </x-ui.alert>
                            </div>

                            <x-ui.input name="customer_name" label="Full name" required placeholder="Jane Smith" />
                            <x-ui.input name="customer_email" type="email" label="Email" required placeholder="you@email.com" />
                            <x-ui.input name="customer_phone" type="tel" label="Phone" required placeholder="0400 000 000" />

                            <div class="flex flex-col">
                                <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Anything we should know? (optional)</label>
                                <textarea id="notes" name="notes"
                                          placeholder="Health conditions, medications, or what you'd like to achieve."
                                          class="min-h-28 w-full rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-sm text-brand-teal-deep shadow-xs placeholder:text-brand-muted/60 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none">{{ old('notes') }}</textarea>
                                @error('notes')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            @if ($squareConfigured && $allowInPerson)
                                <div>
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Payment</span>
                                    <div class="grid gap-2.5">
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3.5 transition"
                                               :class="paymentMethod === 'card' ? 'border-brand-green bg-brand-green/5' : 'border-brand-border'">
                                            <input type="radio" name="payment_method" value="card" x-model="paymentMethod"
                                                   class="mt-0.5 size-4 text-brand-green focus:ring-brand-green">
                                            <span class="flex-1">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="text-sm font-semibold text-brand-teal">Pay now by card</span>
                                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-brand-mist px-1.5 py-0.5 text-[10px] font-medium text-brand-muted">
                                                        <img src="/square-icon.svg" alt="" class="size-3" aria-hidden="true">
                                                        Square
                                                    </span>
                                                </span>
                                                <span class="block text-xs text-brand-muted">Secure online payment.</span>
                                            </span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3.5 transition"
                                               :class="paymentMethod === 'in_person' ? 'border-brand-green bg-brand-green/5' : 'border-brand-border'">
                                            <input type="radio" name="payment_method" value="in_person" x-model="paymentMethod"
                                                   class="mt-0.5 size-4 text-brand-green focus:ring-brand-green">
                                            <span class="flex-1">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="text-sm font-semibold text-brand-teal">Pay at the clinic</span>
                                                    <x-lucide-building-2 class="size-4 shrink-0 text-brand-muted" />
                                                </span>
                                                <span class="block text-xs text-brand-muted">Pay on the day of your appointment.</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="payment_method" :value="paymentMethod">
                            @endif

                            <div class="rounded-lg border border-brand-border bg-brand-mist/40 p-3.5 space-y-3">
                                <p class="text-xs text-brand-muted leading-relaxed">
                                    Macarthur Infusions collects the personal and health information above to provide your treatment, manage your booking and payment, send you appointment reminders, and meet our legal obligations under the
                                    <em>Privacy Act 1988 (Cth)</em>. We store your information in Australia and only share it with the providers needed to run the service (e.g. our payment provider and email provider). You can access or correct your information, or withdraw consent, by contacting us at <a href="mailto:{{ config('booking.clinic.privacy_email', 'privacy@macarthurinfusions.com.au') }}" class="font-semibold text-brand-teal underline">{{ config('booking.clinic.privacy_email', 'privacy@macarthurinfusions.com.au') }}</a>. Full details: <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-brand-teal underline">Privacy Policy</a>.
                                </p>

                                <label class="flex cursor-pointer items-start gap-2.5">
                                    <input type="checkbox" name="consent_privacy" value="1"
                                           {{ old('consent_privacy') ? 'checked' : '' }} required
                                           class="mt-0.5 size-4 rounded border-brand-border text-brand-green focus:ring-brand-green">
                                    <span class="text-sm text-brand-teal-deep">
                                        I have read the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="font-semibold underline">Privacy Policy</a> and Collection Notice and consent to Macarthur Infusions collecting and storing my personal and health information for the purposes described above.
                                        <span class="text-red-600">*</span>
                                    </span>
                                </label>
                                @error('consent_privacy')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <x-ui.button type="submit" variant="primary" class="w-full"
                                         x-bind:disabled="!selectedDate || !selectedTime">
                                <span x-text="paymentMethod === 'in_person' ? 'Confirm booking' : 'Continue to payment'">Continue to booking</span>
                            </x-ui.button>
                            <p class="text-xs text-brand-muted" x-show="paymentMethod === 'card'" x-cloak>Your slot is held for {{ config('booking.hold_minutes') }} minutes while you pay. Payments are processed securely by Square — card details never touch our site.</p>
                            <p class="text-xs text-brand-muted" x-show="paymentMethod === 'in_person'" x-cloak>Your appointment is confirmed straight away. Please bring payment of {{ $service->priceFormatted() }} to the clinic.</p>
                        </form>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function slotPicker() {
    return {
        days: [], loading: true,
        weekStart: null, minStart: null,
        selectedDate: @json(old('date')), selectedTime: @json(old('time')),
        selectedSlots: [], selectedDayLabel: '', rangeLabel: '',
        paymentMethod: @json(old('payment_method', $squareConfigured ? 'card' : 'in_person')),
        step: @json($errors->any() || old('customer_name') ? 2 : 1),

        init() {
            const today = new Date();
            this.minStart = today;
            this.weekStart = today;
            this.fetchWeek();
        },
        fmt(d) { return d.toISOString().slice(0, 10); },
        addDays(d, n) { const c = new Date(d); c.setDate(c.getDate() + n); return c; },
        canGoBack() { return this.weekStart > this.minStart; },
        prevWeek() { this.weekStart = this.addDays(this.weekStart, -7); this.fetchWeek(); },
        nextWeek() { this.weekStart = this.addDays(this.weekStart, 7); this.fetchWeek(); },

        async fetchWeek() {
            this.loading = true;
            const from = this.fmt(this.weekStart), to = this.fmt(this.addDays(this.weekStart, 6));
            const res = await fetch(`{{ route('booking.availability', $service) }}?from=${from}&to=${to}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.days = Object.entries(data.days).map(([date, slots]) => {
                const d = new Date(date + 'T12:00:00');
                return {
                    date, slots,
                    hasFree: slots.some(s => s.available),
                    weekday: d.toLocaleDateString('en-AU', { weekday: 'short' }),
                    dayNum: d.getDate(),
                    label: d.toLocaleDateString('en-AU', { weekday: 'long', day: 'numeric', month: 'long' }),
                };
            });
            const first = new Date(from + 'T12:00:00'), last = new Date(to + 'T12:00:00');
            this.rangeLabel = first.toLocaleDateString('en-AU', { day: 'numeric', month: 'short' }) + ' – ' + last.toLocaleDateString('en-AU', { day: 'numeric', month: 'short' });
            const existing = this.selectedDate ? this.days.find(d => d.date === this.selectedDate) : null;
            if (existing) {
                this.selectedSlots = existing.slots;
                this.selectedDayLabel = existing.label;
            } else {
                const auto = this.days.find(d => d.hasFree) || this.days.find(d => d.slots.length);
                if (auto) {
                    this.selectDay(auto);
                } else {
                    this.selectedDate = null;
                    this.selectedSlots = [];
                }
            }
            this.loading = false;
        },
        selectDay(day) {
            this.selectedDate = day.date;
            this.selectedDayLabel = day.label;
            this.selectedSlots = day.slots;
            this.selectedTime = null;
        },
    };
}
</script>
@endpush
@endsection
