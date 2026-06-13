{{-- Preline overlay modal for creating a manual (phone/walk-in) booking. --}}
<div id="create-booking-modal"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1" aria-labelledby="create-booking-label">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="flex flex-col bg-white border border-brand-border rounded-2xl shadow-lg pointer-events-auto">
            <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                <h3 id="create-booking-label" class="font-display text-lg font-semibold text-brand-teal">Create booking</h3>
                <button type="button"
                        class="inline-flex size-8 items-center justify-center rounded-full text-brand-muted hover:bg-brand-mist"
                        data-hs-overlay="#create-booking-modal" aria-label="Close">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <form method="post" action="{{ route('admin.bookings.store') }}" class="flex flex-col">
                @csrf
                <div class="space-y-4 px-5 py-5">
                    <p class="text-sm text-brand-muted">For phone or walk-in patients — no online payment is taken.</p>

                    @php
                        $cbChevron = '<svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
                        $cbCheck = '<svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
                        $cbSelectConfig = [
                            'placeholder' => 'Select a service…',
                            'hasSearch' => true,
                            'searchPlaceholder' => 'Search services…',
                            'searchWrapperClasses' => 'bg-white p-1 -mx-1 sticky top-0',
                            'searchClasses' => 'block w-full text-sm rounded-lg border border-brand-border px-3 py-2 mb-1 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none',
                            'toggleTag' => '<button type="button" aria-expanded="false"></button>',
                            'toggleClasses' => 'hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative flex w-full items-center justify-between gap-2 rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-start text-sm text-brand-teal-deep focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none',
                            'toggleSeparators' => ['items' => ' · '],
                            'dropdownClasses' => 'mt-2 z-[90] w-full max-h-72 p-1 space-y-0.5 bg-white border border-brand-border rounded-lg overflow-hidden overflow-y-auto shadow-lg',
                            'optionClasses' => 'flex items-center justify-between gap-2 py-2 px-3 w-full text-sm text-brand-teal-deep cursor-pointer rounded-lg hover:bg-brand-mist focus:outline-none focus:bg-brand-mist hs-selected:bg-brand-mist',
                            'optionTemplate' => '<div class="flex w-full items-center justify-between"><span data-title></span><span class="hidden hs-selected:block text-brand-green">'.$cbCheck.'</span></div>',
                            'extraMarkup' => '<div class="absolute top-1/2 end-3 -translate-y-1/2 text-brand-muted">'.$cbChevron.'</div>',
                        ];
                    @endphp
                    <div>
                        <label for="cb_service" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Service</label>
                        <select id="cb_service" name="service_id" required data-hs-select='@json($cbSelectConfig)' class="hidden">
                            @foreach ($bookableServices as $service)
                                <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>
                                    {{ $service->name }} — {{ $service->priceFormatted() }} ({{ $service->duration_minutes }}min)
                                </option>
                            @endforeach
                        </select>
                        @error('service_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="cb_date" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Date</label>
                            <input id="cb_date" type="date" name="date" required value="{{ old('date') }}"
                                   class="block w-full rounded-lg border border-brand-border px-3.5 py-2.5 text-sm">
                            @error('date')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="cb_time" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Time</label>
                            <input id="cb_time" type="time" name="time" required step="900" value="{{ old('time') }}"
                                   class="block w-full rounded-lg border border-brand-border px-3.5 py-2.5 text-sm">
                            @error('time')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <x-ui.input name="customer_name" label="Patient name" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input name="customer_email" type="email" label="Email" required />
                        <x-ui.input name="customer_phone" type="tel" label="Phone" required />
                    </div>
                    <x-ui.input name="notes" type="textarea" label="Notes (optional)" />
                </div>

                <div class="flex justify-end gap-2 border-t border-brand-border px-5 py-4">
                    <x-ui.button type="button" variant="ghost" data-hs-overlay="#create-booking-modal">Cancel</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Create booking</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (request()->boolean('create') || $errors->hasAny(['service_id', 'date', 'time', 'customer_name', 'customer_email', 'customer_phone']))
    {{-- Open on ?create=1 deep-link, or re-open after a validation/overlap error so input isn't lost. --}}
    @push('scripts')
        <script>
            window.addEventListener('load', () => {
                const el = document.querySelector('#create-booking-modal');
                if (!el) return;
                if (window.HSOverlay?.open) {
                    window.HSOverlay.open(el);
                } else {
                    document.querySelector('[data-hs-overlay="#create-booking-modal"]')?.click();
                }
            });
        </script>
    @endpush
@endif
