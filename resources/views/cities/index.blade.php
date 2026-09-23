<x-layouts.public :title="__('discover.cities_page.heading')">
    <x-catalog-hero :title="__('discover.cities_page.heading')" :subtitle="__('discover.cities_page.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.cities') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8">
        @forelse ($governorates as $governorate)
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ $governorate->name }}</h2>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($governorate->cities as $city)
                        <a href="{{ route('cities.show', $city) }}" class="card px-4 py-3 text-sm font-medium text-ink-700 hover:ring-2 hover:ring-primary-200">
                            {{ $city->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <x-empty-state :message="__('discover.cities_page.empty')"/>
        @endforelse
    </div>
</x-layouts.public>
