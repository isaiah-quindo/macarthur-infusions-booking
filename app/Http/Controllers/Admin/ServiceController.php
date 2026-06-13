<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('display_order')->get()->groupBy('category');
        $categories = ServiceCategory::orderBy('display_order')->get();

        return view('admin.services.index', compact('services', 'categories'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('service_categories', 'name')],
        ]);

        ServiceCategory::create([
            'name' => $data['name'],
            'display_order' => (ServiceCategory::max('display_order') ?? -1) + 1,
        ]);

        return redirect()->route('admin.services.index')->with('status', $data['name'].' added.');
    }

    public function destroyCategory(ServiceCategory $category)
    {
        if (Service::where('category', $category->name)->exists()) {
            return back()->with('error', 'Move or delete the services in '.$category->name.' before removing the category.');
        }

        $category->delete();

        return redirect()->route('admin.services.index')->with('status', $category->name.' removed.');
    }

    public function create()
    {
        return view('admin.services.create', [
            'service' => new Service(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->cleanedPayload($this->validated($request));
        $data['image_path'] = $this->handleImageUpload($request);

        $service = Service::create($data);

        return redirect()->route('admin.services.index')->with('status', $service->name.' created.');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        // Quick toggle from the list view: only the `is_active` flag is posted.
        if ($request->has('quick_toggle')) {
            $service->update(['is_active' => $request->boolean('is_active')]);
            return back()->with('status', $service->name.($service->is_active ? ' activated.' : ' deactivated.'));
        }

        $data = $this->cleanedPayload($this->validated($request, $service));

        $newImagePath = $this->handleImageUpload($request, $service);
        if ($newImagePath !== null) {
            $data['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($service->image_path);
            $data['image_path'] = null;
        }

        $service->update($data);

        return redirect()->route('admin.services.index')->with('status', $service->name.' updated.');
    }

    public function destroy(Service $service)
    {
        $this->deleteImage($service->image_path);
        $service->delete();

        return redirect()->route('admin.services.index')->with('status', $service->name.' deleted.');
    }

    private function handleImageUpload(Request $request, ?Service $service = null): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        if (! $this->supabaseConfigured()) {
            abort(422, 'Image upload is not configured — set SUPABASE_BUCKET (and key/secret) in .env first.');
        }

        // Drop the previous image so we don't accumulate orphans in the bucket.
        if ($service?->image_path) {
            $this->deleteImage($service->image_path);
        }

        return $request->file('image')->store('services', 'supabase');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && $this->supabaseConfigured()) {
            Storage::disk('supabase')->delete($path);
        }
    }

    private function supabaseConfigured(): bool
    {
        return (bool) config('filesystems.disks.supabase.bucket');
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('services', 'slug')->ignore($service?->id)],
            'description' => ['nullable', 'string'],
            'price_dollars' => ['required', 'numeric', 'min:0', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'included' => ['nullable', 'array'],
            'included.*' => ['nullable', 'string', 'max:500'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['nullable', 'string', 'max:200'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function cleanedPayload(array $data): array
    {
        $data['slug'] = Str::slug(! empty($data['slug']) ? $data['slug'] : $data['name']);
        $data['price_cents'] = (int) round($data['price_dollars'] * 100);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['display_order'] = $data['display_order'] ?? 0;
        unset($data['price_dollars']);

        $data['included'] = collect($data['included'] ?? [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();

        $data['benefits'] = collect($data['benefits'] ?? [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();

        $data['faqs'] = collect($data['faqs'] ?? [])
            ->map(fn ($row) => [
                'question' => trim((string) ($row['question'] ?? '')),
                'answer' => trim((string) ($row['answer'] ?? '')),
            ])
            ->filter(fn ($row) => $row['question'] !== '' || $row['answer'] !== '')
            ->values()
            ->all();

        return $data;
    }
}
