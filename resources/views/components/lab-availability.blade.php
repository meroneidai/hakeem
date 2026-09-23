@props(['offerings'])

@php
    $names = collect($offerings)
        ->map(fn ($offering) => $offering->clinic?->name)
        ->filter()
        ->unique()
        ->values();
    $homeOfferings = collect($offerings)->filter(fn ($offering) => $offering->allows_home_collection);
    $clinicFrom = collect($offerings)->map(fn ($offering) => $offering->clinicPrice())->filter()->min();
    $homeFrom = $homeOfferings->map(fn ($offering) => $offering->homeCollectionPrice())->filter()->min();
@endphp

@if ($names->isNotEmpty())
    <p {{ $attributes->merge(['class' => 'mt-2 text-xs leading-5 text-ink-500']) }}>
        <span class="font-medium text-ink-700">{{ __('labs.available_at') }}:</span>
        {{ $names->take(3)->implode(' · ') }}
        @if ($names->count() > 3)
            <span>{{ __('labs.more_labs', ['count' => $names->count() - 3]) }}</span>
        @endif
        @if ($clinicFrom)
            <span class="mt-1 block">
                {{ __('labs.price_clinic') }}:
                <span class="font-medium text-ink-700">{{ number_format($clinicFrom) }} {{ __('common.currency') }}</span>
                @if ($homeOfferings->isNotEmpty() && $homeFrom)
                    <span class="mx-1 text-ink-300">·</span>
                    {{ __('labs.price_home') }}:
                    <span class="font-medium text-ink-700">{{ number_format($homeFrom) }} {{ __('common.currency') }}</span>
                @endif
            </span>
        @endif
    </p>
@endif
