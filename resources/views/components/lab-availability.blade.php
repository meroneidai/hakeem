@props(['offerings'])

@php
    $names = collect($offerings)
        ->map(fn ($offering) => $offering->clinic?->name)
        ->filter()
        ->unique()
        ->values();
@endphp

@if ($names->isNotEmpty())
    <p {{ $attributes->merge(['class' => 'mt-2 text-xs leading-5 text-ink-500']) }}>
        <span class="font-medium text-ink-700">{{ __('labs.available_at') }}:</span>
        {{ $names->take(3)->implode(' · ') }}
        @if ($names->count() > 3)
            <span>{{ __('labs.more_labs', ['count' => $names->count() - 3]) }}</span>
        @endif
    </p>
@endif
