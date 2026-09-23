@php
    $heading = $place
        ? __('discover.specialties_page.in_place', ['specialty' => $specialty->name, 'place' => $place->name])
        : $specialty->name;
@endphp

<x-layouts.public :title="$heading">
    <x-catalog-hero :title="$heading" :subtitle="$specialty->description">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('specialties.index') }}" class="hover:text-primary-700">{{ __('discover.nav.specialties') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('specialties.show', $specialty) }}" class="hover:text-primary-700">{{ $specialty->name }}</a>
            @if ($place)
                <span aria-hidden="true">·</span>
                <span class="text-ink-700">{{ $place->name }}</span>
            @endif
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        @if (($cities ?? collect())->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach ($cities as $city)
                    <a href="{{ route('specialties.location', [$specialty, $city->slug]) }}" class="rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200 hover:ring-primary-300">{{ $city->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($doctors as $doctor)
                <x-doctor-card :doctor="$doctor"/>
            @empty
                <x-card class="sm:col-span-2">
                    <x-empty-state :message="__('discover.doctors.empty')"/>
                </x-card>
            @endforelse
        </div>
    </div>
</x-layouts.public>
