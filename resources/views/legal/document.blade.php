@extends('layouts.app')

@section('title', $title)

@push('head')
<style>
    .legal-doc h1 { font-size: 2rem; font-weight: 600; color: var(--color-brand-teal, #0f4c5c); margin-bottom: 1rem; }
    .legal-doc h2 { font-size: 1.25rem; font-weight: 600; color: var(--color-brand-teal, #0f4c5c); margin-top: 2rem; margin-bottom: 0.75rem; }
    .legal-doc h3 { font-size: 1.05rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
    .legal-doc p { margin-bottom: 1rem; line-height: 1.65; color: #475569; }
    .legal-doc strong { color: #0f172a; }
    .legal-doc ul, .legal-doc ol { margin: 0 0 1rem 1.5rem; color: #475569; line-height: 1.65; }
    .legal-doc ul { list-style: disc; }
    .legal-doc ol { list-style: decimal; }
    .legal-doc li { margin-bottom: 0.25rem; }
    .legal-doc a { color: #2c7a7b; text-decoration: underline; }
    .legal-doc a:hover { color: #1f5454; }
    .legal-doc table { width: 100%; margin: 1rem 0; border-collapse: collapse; }
    .legal-doc th, .legal-doc td { padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; text-align: left; font-size: 0.95rem; }
    .legal-doc th { background: #f9fafb; font-weight: 600; }
    .legal-doc hr { margin: 2rem 0; border: 0; border-top: 1px solid #e5e7eb; }
    .legal-doc code { font-family: ui-monospace, monospace; font-size: 0.9em; background: #f1f5f9; padding: 0.1em 0.35em; border-radius: 0.25rem; }
</style>
@endpush

@section('content')
    <article class="legal-doc mx-auto max-w-3xl">
        {!! $html !!}
    </article>
@endsection
