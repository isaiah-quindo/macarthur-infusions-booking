@extends('layouts.admin')

@section('title', 'Services')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Services</h1>
            <p class="mt-1 text-sm text-brand-muted">Manage the services patients can book. Inactive ones disappear from the public booking page.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="outline" data-hs-overlay="#add-category-modal">
                <x-lucide-plus class="size-4" />
                Add category
            </x-ui.button>
            <x-ui.button variant="primary" :href="route('admin.services.create')">
                <x-lucide-plus class="size-4" />
                New service
            </x-ui.button>
        </div>
    </div>

    @forelse ($categories as $category)
        @php($items = $services->get($category->name, collect()))
        <div class="mt-8">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">{{ $category->name }}</h2>
                @if ($items->isEmpty())
                    <form method="post" action="{{ route('admin.services.categories.destroy', $category) }}"
                          onsubmit="return confirm('Remove this category?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="cursor-pointer text-xs font-semibold text-red-600 hover:underline">Remove category</button>
                    </form>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="rounded-xl border border-dashed border-brand-border bg-white/60 px-4 py-6 text-center text-sm text-brand-muted">
                    No services in this category yet.
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-brand-border bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-brand-mist text-left text-xs font-semibold uppercase tracking-wider text-brand-muted">
                            <tr>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3 w-28">Price</th>
                                <th class="px-4 py-3 w-28">Duration</th>
                                <th class="px-4 py-3 w-20 text-center">Active</th>
                                <th class="px-4 py-3 w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            @foreach ($items as $service)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-brand-teal">{{ $service->name }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $service->priceFormatted() }}</td>
                                    <td class="px-4 py-3">{{ $service->duration_minutes }} min</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($service->is_active)
                                            <x-lucide-check class="inline size-4 text-brand-green" />
                                        @else
                                            <span class="text-brand-muted/40">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-ui.button variant="ghost" :href="route('admin.services.edit', $service)" class="!px-3 !py-1.5 !text-xs">
                                            <x-lucide-pencil class="size-3.5" />
                                            Edit
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="mt-8 rounded-xl border border-dashed border-brand-border bg-white/60 px-4 py-12 text-center">
            <p class="text-brand-muted">No categories yet — add one to get started.</p>
            <x-ui.button variant="primary" data-hs-overlay="#add-category-modal" class="mt-4">
                <x-lucide-plus class="size-4" />
                Add your first category
            </x-ui.button>
        </div>
    @endforelse

    {{-- Add Category modal --}}
    <div id="add-category-modal"
         class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
         role="dialog" tabindex="-1" aria-labelledby="add-category-label">
        <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-md sm:w-full m-3 sm:mx-auto">
            <div class="flex flex-col bg-white border border-brand-border rounded-2xl shadow-lg pointer-events-auto">
                <div class="flex items-center justify-between border-b border-brand-border px-5 py-4">
                    <h3 id="add-category-label" class="font-display text-lg font-semibold text-brand-teal">Add category</h3>
                    <button type="button"
                            class="cursor-pointer inline-flex size-8 items-center justify-center rounded-full text-brand-muted hover:bg-brand-mist"
                            data-hs-overlay="#add-category-modal" aria-label="Close">
                        <x-lucide-x class="size-4" />
                    </button>
                </div>

                <form method="post" action="{{ route('admin.services.categories.store') }}">
                    @csrf
                    <div class="space-y-3 px-5 py-5">
                        <div>
                            <label for="cat_name" class="block text-xs font-semibold uppercase tracking-wider text-brand-muted mb-2">Name</label>
                            <input id="cat_name" name="name" type="text" required value="{{ old('name') }}"
                                   placeholder="e.g. Weight Management"
                                   class="block w-full rounded-lg border border-brand-border bg-white px-3.5 py-2.5 text-sm text-brand-teal-deep focus:border-brand-green focus:ring-2 focus:ring-brand-green/25 focus:outline-none">
                            @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-brand-border px-5 py-4">
                        <x-ui.button type="button" variant="ghost" data-hs-overlay="#add-category-modal">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Add category</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->has('name') || old('name'))
        @push('scripts')
            <script>
                window.addEventListener('load', () => {
                    const el = document.querySelector('#add-category-modal');
                    if (el && window.HSOverlay?.open) window.HSOverlay.open(el);
                });
            </script>
        @endpush
    @endif
@endsection
