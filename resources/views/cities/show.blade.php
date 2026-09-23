<x-layouts.public :title="$city->name">
    <x-catalog-hero :title="$city->name" :subtitle="__('discover.cities_page.in_city', ['city' => $city->name])">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('cities.index') }}" class="hover:text-primary-700">{{ __('discover.nav.cities') }}</a>
            @if ($city->governorate)
                <span aria-hidden="true">·</span>
                <span class="text-ink-700">{{ $city->governorate->name }}</span>
            @endif
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <section class="mb-10">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.clinics') }}</h2>
                <a href="{{ route('clinics.index', ['city' => $city->slug, 'governorate' => $city->governorate?->slug]) }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
            </div>
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($clinics as $clinic)
                    <x-clinic-card :clinic="$clinic"/>
                @empty
                    <x-card class="sm:col-span-2 lg:col-span-3">
                        <x-empty-state :message="__('discover.cities_page.empty')"/>
                    </x-card>
                @endforelse
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.doctors') }}</h2>
                <a href="{{ route('doctors.index', ['city' => $city->slug, 'governorate' => $city->governorate?->slug]) }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($doctors as $doctor)
                    <x-doctor-card :doctor="$doctor"/>
                @empty
                    <x-card class="sm:col-span-2">
                        <x-empty-state :message="__('discover.doctors.empty')"/>
                    </x-card>
                @endforelse
            </div>
        </section>

        @if (($nearby ?? collect())->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('discover.cities_page.nearby') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($nearby as $area)
                        <a href="{{ route('cities.show', $area) }}" class="rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200 hover:ring-primary-300">{{ $area->name }}</a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
