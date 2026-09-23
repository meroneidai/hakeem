@php
    $heading = ($place ?? null)
        ? __('discover.services_page.in_place', ['service' => $serviceType->name, 'place' => $place->name])
        : $serviceType->name;
@endphp

<x-layouts.public :title="$heading">
    <x-catalog-hero :title="$heading" :subtitle="$serviceType->description">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('services.index') }}" class="hover:text-primary-700">{{ __('discover.nav.services') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('services.show', $serviceType) }}" class="hover:text-primary-700">{{ $serviceType->name }}</a>
            @if ($place ?? null)
                <span aria-hidden="true">·</span>
                <span class="text-ink-700">{{ $place->name }}</span>
            @endif
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 pb-10">
        @include('services._directory')
    </div>
</x-layouts.public>
