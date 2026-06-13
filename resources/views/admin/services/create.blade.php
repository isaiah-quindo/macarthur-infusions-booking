@extends('layouts.admin')

@section('title', 'New service')

@section('content')
    <x-ui.button variant="ghost" :href="route('admin.services.index')">
        <x-lucide-arrow-left class="size-4" />
        Services
    </x-ui.button>

    <h1 class="mt-4 text-2xl font-semibold">New service</h1>
    <p class="mt-1 text-sm text-brand-muted">Fill in the basics — you can flesh out the details after creating it.</p>

    <div class="mt-6">
        @include('admin.services._form', [
            'service' => $service,
            'action' => route('admin.services.store'),
            'method' => 'POST',
        ])
    </div>
@endsection
