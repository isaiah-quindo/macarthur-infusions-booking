@props(['service', 'action', 'method' => 'POST'])

@php
    $inputClasses = 'block w-full rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-sm text-brand-teal-deep shadow-xs placeholder:text-brand-muted/60 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none';
    $labelClasses = 'block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2';

    $included = old('included', $service->included ?? []);
    $benefits = old('benefits', $service->benefits ?? []);
    $faqs = old('faqs', $service->faqs ?? []);

    $categories = \App\Models\ServiceCategory::orderBy('display_order')->pluck('name');
    $selectedCategory = old('category', $service->category);

    $catChevron = '<svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    $catCheck = '<svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
    $catSelectConfig = [
        'placeholder' => 'Select a category…',
        'hasSearch' => true,
        'searchPlaceholder' => 'Search categories…',
        'searchWrapperClasses' => 'bg-white p-1 -mx-1 sticky top-0',
        'searchClasses' => 'block w-full text-sm rounded-lg border border-brand-border px-3 py-2 mb-1 focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none',
        'toggleTag' => '<button type="button" aria-expanded="false"></button>',
        'toggleClasses' => 'cursor-pointer hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative flex w-full items-center justify-between gap-2 rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-start text-sm text-brand-teal-deep focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none',
        'dropdownClasses' => 'mt-2 z-[90] w-full max-h-72 p-1 space-y-0.5 bg-white border border-brand-border rounded-lg overflow-hidden overflow-y-auto shadow-lg',
        'optionClasses' => 'flex items-center justify-between gap-2 py-2 px-3 w-full text-sm text-brand-teal-deep cursor-pointer rounded-lg hover:bg-brand-mist focus:outline-none focus:bg-brand-mist hs-selected:bg-brand-mist',
        'optionTemplate' => '<div class="flex w-full items-center justify-between"><span data-title></span><span class="hidden hs-selected:block text-brand-green">'.$catCheck.'</span></div>',
        'extraMarkup' => '<div class="absolute top-1/2 end-3 -translate-y-1/2 text-brand-muted">'.$catChevron.'</div>',
    ];
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data"
      x-data="serviceForm({
          included: @js($included),
          benefits: @js($benefits),
          faqs: @js($faqs),
          existingImage: @js($service->imageUrl()),
      })"
      class="space-y-8">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Basics --}}
    <x-ui.card>
        <h2 class="text-lg font-semibold">Basics</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="{{ $labelClasses }}">Name <span class="text-brand-orange">*</span></label>
                <input id="name" name="name" type="text" required value="{{ old('name', $service->name) }}" class="{{ $inputClasses }}">
                @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="category" class="{{ $labelClasses }}">Category <span class="text-brand-orange">*</span></label>
                <select id="category" name="category" required data-hs-select='@json($catSelectConfig)' class="hidden">
                    <option value=""></option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

<div>
                <label for="slug" class="{{ $labelClasses }}">Slug</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug', $service->slug) }}" placeholder="auto from name" class="{{ $inputClasses }}">
                <p class="mt-1 text-xs text-brand-muted">URL: <code>/service/&lt;slug&gt;</code>. Leave blank to auto-generate.</p>
                @error('slug')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_order" class="{{ $labelClasses }}">Display order</label>
                <input id="display_order" name="display_order" type="number" min="0" value="{{ old('display_order', $service->display_order ?? 0) }}" class="{{ $inputClasses }}">
                @error('display_order')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="price_dollars" class="{{ $labelClasses }}">Price ($) <span class="text-brand-orange">*</span></label>
                <input id="price_dollars" name="price_dollars" type="number" step="0.01" min="0" required
                       value="{{ old('price_dollars', $service->price_cents !== null ? $service->price_cents / 100 : '') }}" class="{{ $inputClasses }}">
                @error('price_dollars')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="duration_minutes" class="{{ $labelClasses }}">Duration (minutes) <span class="text-brand-orange">*</span></label>
                <input id="duration_minutes" name="duration_minutes" type="number" step="5" min="5" max="480" required value="{{ old('duration_minutes', $service->duration_minutes ?? 60) }}" class="{{ $inputClasses }}">
                @error('duration_minutes')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-brand-teal cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true))
                           class="size-4 rounded border-brand-border text-brand-green focus:ring-brand-green">
                    Active (visible on the public booking page)
                </label>
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="{{ $labelClasses }}">About this treatment</label>
                <textarea id="description" name="description" rows="6"
                          placeholder="Long-form copy. Use a blank line to separate paragraphs."
                          class="{{ $inputClasses }}">{{ old('description', $service->description) }}</textarea>
                @error('description')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </x-ui.card>

    {{-- Hero image --}}
    <x-ui.card>
        <h2 class="text-lg font-semibold">Hero image</h2>
        <p class="mt-1 text-sm text-brand-muted">Shown at the top of the public booking page. JPG/PNG/WebP, up to 5 MB.</p>

        <div class="mt-4 space-y-3">
            {{-- Preview --}}
            <template x-if="previewUrl || existingImage">
                <div class="relative aspect-[16/9] w-full max-w-md overflow-hidden rounded-xl border border-brand-border bg-brand-mist">
                    <img :src="previewUrl || existingImage" alt="" class="size-full object-cover" />
                </div>
            </template>

            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                   @change="previewImage($event)"
                   class="block w-full text-sm text-brand-teal-deep file:mr-3 file:cursor-pointer file:rounded-lg file:border file:border-brand-border file:bg-white file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-teal hover:file:bg-brand-mist">
            @error('image')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror

            <template x-if="existingImage && !previewUrl">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-red-600">
                    <input type="checkbox" name="remove_image" value="1"
                           class="size-4 rounded border-brand-border text-red-600 focus:ring-red-600">
                    Remove current image
                </label>
            </template>
        </div>
    </x-ui.card>

    {{-- What's included --}}
    <x-ui.card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">What's included</h2>
                <p class="mt-1 text-sm text-brand-muted">Bullets shown as a checked list.</p>
            </div>
            <x-ui.button type="button" variant="outline" @click="included.push('')">
                <x-lucide-plus class="size-4" />
                Add item
            </x-ui.button>
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(item, i) in included" :key="i">
                <div class="flex items-start gap-2">
                    <input type="text" :name="`included[${i}]`" x-model="included[i]"
                           placeholder="e.g. Pre-treatment consultation"
                           class="{{ $inputClasses }}">
                    <button type="button" @click="included.splice(i, 1)" class="cursor-pointer rounded-lg border border-brand-border p-2.5 text-brand-muted hover:bg-brand-mist hover:text-red-600">
                        <x-lucide-trash-2 class="size-4" />
                    </button>
                </div>
            </template>
            <p x-show="!included.length" class="text-sm text-brand-muted">No items yet — click "Add item" to start.</p>
        </div>
    </x-ui.card>

    {{-- Benefits --}}
    <x-ui.card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Benefits</h2>
                <p class="mt-1 text-sm text-brand-muted">Bullets shown as a checked list.</p>
            </div>
            <x-ui.button type="button" variant="outline" @click="benefits.push('')">
                <x-lucide-plus class="size-4" />
                Add benefit
            </x-ui.button>
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(item, i) in benefits" :key="i">
                <div class="flex items-start gap-2">
                    <input type="text" :name="`benefits[${i}]`" x-model="benefits[i]"
                           placeholder="e.g. Supports normal energy metabolism"
                           class="{{ $inputClasses }}">
                    <button type="button" @click="benefits.splice(i, 1)" class="cursor-pointer rounded-lg border border-brand-border p-2.5 text-brand-muted hover:bg-brand-mist hover:text-red-600">
                        <x-lucide-trash-2 class="size-4" />
                    </button>
                </div>
            </template>
            <p x-show="!benefits.length" class="text-sm text-brand-muted">No benefits yet — click "Add benefit" to start.</p>
        </div>
    </x-ui.card>

    {{-- FAQs --}}
    <x-ui.card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">FAQs</h2>
                <p class="mt-1 text-sm text-brand-muted">Question + answer per row.</p>
            </div>
            <x-ui.button type="button" variant="outline" @click="faqs.push({question: '', answer: ''})">
                <x-lucide-plus class="size-4" />
                Add FAQ
            </x-ui.button>
        </div>

        <div class="mt-4 space-y-4">
            <template x-for="(item, i) in faqs" :key="i">
                <div class="rounded-lg border border-brand-border p-3">
                    <div class="flex items-start gap-2">
                        <div class="flex-1 space-y-2">
                            <input type="text" :name="`faqs[${i}][question]`" x-model="faqs[i].question"
                                   placeholder="How long does the appointment take?" class="{{ $inputClasses }} font-semibold">
                            <textarea :name="`faqs[${i}][answer]`" rows="3" x-model="faqs[i].answer"
                                      placeholder="The infusion itself takes about…"
                                      class="{{ $inputClasses }}"></textarea>
                        </div>
                        <button type="button" @click="faqs.splice(i, 1)" class="cursor-pointer rounded-lg border border-brand-border p-2.5 text-brand-muted hover:bg-brand-mist hover:text-red-600">
                            <x-lucide-trash-2 class="size-4" />
                        </button>
                    </div>
                </div>
            </template>
            <p x-show="!faqs.length" class="text-sm text-brand-muted">No FAQs yet.</p>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-between gap-3">
        <x-ui.button type="button" variant="ghost" :href="route('admin.services.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $service->exists ? 'Save changes' : 'Create service' }}</x-ui.button>
    </div>
</form>

@push('scripts')
<script>
function serviceForm({ included, benefits, faqs, existingImage }) {
    return {
        included: Array.isArray(included) ? included : [],
        benefits: Array.isArray(benefits) ? benefits : [],
        faqs: Array.isArray(faqs) ? faqs : [],
        existingImage: existingImage || null,
        previewUrl: null,
        previewImage(event) {
            const file = event.target.files?.[0];
            this.previewUrl = file ? URL.createObjectURL(file) : null;
        },
    };
}
</script>
@endpush
