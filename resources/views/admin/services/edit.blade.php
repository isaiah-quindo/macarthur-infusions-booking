@extends('layouts.admin')

@section('title', 'Edit '.$service->name)

@section('content')
    <x-ui.button variant="ghost" :href="route('admin.services.index')">
        <x-lucide-arrow-left class="size-4" />
        Services
    </x-ui.button>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ $service->name }}</h1>
            <p class="mt-1 text-sm text-brand-muted">{{ $service->category }} · /service/{{ $service->slug }}</p>
        </div>

        <form method="post" action="{{ route('admin.services.destroy', $service) }}"
              onsubmit="return confirm('Delete this service? Existing bookings will keep their reference but the service won\'t be bookable.')">
            @csrf @method('DELETE')
            <x-ui.button type="submit" variant="danger">
                <x-lucide-trash-2 class="size-4" />
                Delete
            </x-ui.button>
        </form>
    </div>

    <div class="mt-6">
        @include('admin.services._form', [
            'service' => $service,
            'action' => route('admin.services.update', $service),
            'method' => 'PATCH',
        ])
    </div>
@endsection
