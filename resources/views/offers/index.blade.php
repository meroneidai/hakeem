<x-layouts.public :title="__('offers.heading')">
    <x-catalog-hero :title="__('offers.heading')" :subtitle="__('offers.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.offers') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">

        <form method="GET" class="mb-6 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <x-input name="q" :value="request('q')" :placeholder="__('common.search')"/>
            <x-select name="governorate" :placeholder="__('admin.cities.governorate')"
                      :options="$governorates->pluck('name', 'slug')->all()" :selected="request('governorate')"/>
            <x-select name="city" :placeholder="__('discover.doctors.any_city')"
                      :options="$governorates->flatMap->cities->pluck('name', 'slug')->all()" :selected="request('city')"/>
            <x-input name="max_price" type="number" min="0" :value="$maxPrice ?? ''" :placeholder="__('discover.search.max_price')"/>
            <x-button variant="secondary">{{ __('common.filter') }}</x-button>
            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('offers.index', array_filter(['q' => request('q'), 'governorate' => request('governorate'), 'city' => request('city')])) }}"
               @class(['rounded-full px-3 py-1.5 text-sm font-medium', request('category') ? 'bg-ink-100 text-ink-600' : 'bg-accent-500 text-white'])>
                {{ __('offers.all') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('offers.index', array_filter(['category' => $category->value, 'q' => request('q'), 'governorate' => request('governorate'), 'city' => request('city')])) }}"
                   @class(['rounded-full px-3 py-1.5 text-sm font-medium', $activeCategory === $category->value ? 'bg-accent-500 text-white' : 'bg-ink-100 text-ink-600 hover:bg-ink-200'])>
                    {{ $category->label() }}
                </a>
            @endforeach
            <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($offers as $offer)
                <x-offer-card :offer="$offer"/>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state :message="__('offers.empty')"/>
                </x-card>
            @endforelse
        </div>

        @if ($offers->hasPages())
            <div class="mt-6">{{ $offers->links() }}</div>
        @endif
    </div>
</x-layouts.public>
