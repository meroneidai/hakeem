@php
    $cityMap = $governorates->mapWithKeys(fn ($governorate) => [
        $governorate->slug => $governorate->cities->map(fn ($city) => [
            'slug' => $city->slug,
            'name' => $city->name,
        ])->values(),
    ]);
@endphp

<x-layouts.public :title="__('discover.search.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('discover.search.heading')" :subtitle="__('discover.search.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.search.heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 pb-10"
         x-data="liveFilters({
             q: @js($filters['q'] ?? ''),
             type: @js($type),
             specialty: @js($filters['specialty'] ?? ''),
             governorate: @js($filters['governorate'] ?? ''),
             city: @js($filters['city'] ?? ''),
             gender: @js($filters['gender'] ?? ''),
             min_experience: @js((string) ($filters['min_experience'] ?? '')),
             category: @js($filters['category'] ?? ''),
             max_price: @js((string) ($filters['max_price'] ?? '')),
             cities: {{ \Illuminate\Support\Js::from($cityMap) }},
             endpoint: @js(route('search')),
             target: 'search-results',
         })">
        <x-provider-filters
            :action="route('search')"
            :filters="$filters"
            :specialties="$specialties"
            :governorates="$governorates"
            live
            show-type
            :type="$type"
        >
            <details class="mt-3">
                <summary class="cursor-pointer list-none rounded-xl bg-ink-50 px-3 py-2 text-sm font-medium text-ink-700 marker:content-none">
                    {{ __('discover.search.more_filters') }}
                </summary>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <select name="governorate" x-model="governorate" @change="schedule()" class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="">{{ __('admin.cities.governorate') }}</option>
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate->slug }}">{{ $governorate->name }}</option>
                        @endforeach
                    </select>
                    <x-input name="min_experience" type="number" min="0" x-model="min_experience" @input="schedule()"
                             :value="$filters['min_experience'] ?? ''" :placeholder="__('discover.doctors.min_experience')"/>
                    <x-input name="max_price" type="number" min="0" x-model="max_price" @input="schedule()"
                             :value="$filters['max_price'] ?? ''" :placeholder="__('discover.search.max_price')"/>
                </div>
            </details>
        </x-provider-filters>

        <p x-show="loading" x-cloak class="mb-4 text-sm text-primary-700">{{ __('discover.search.live') }}…</p>

        <div id="search-results" :class="loading ? 'opacity-60 transition' : 'transition'">
            @fragment('results')
                @if (! $hasFilters)
                    <x-card>
                        <x-empty-state :message="__('discover.search.hint')"/>
                    </x-card>
                @else
                    @if (in_array($type, ['all', 'doctors'], true) || ($type === 'services' && $doctors->isNotEmpty()))
                        <section class="mb-8">
                            <div class="mb-4 flex items-end justify-between gap-3">
                                <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.doctors') }}</h2>
                                <p class="text-sm text-ink-500">{{ __('discover.search.results', ['count' => $doctors->count()]) }}</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                @forelse ($doctors as $doctor)
                                    <x-doctor-card :doctor="$doctor"/>
                                @empty
                                    @if ($type === 'doctors')
                                        <x-card class="md:col-span-2"><x-empty-state :message="__('discover.search.empty')"/></x-card>
                                    @endif
                                @endforelse
                            </div>
                        </section>
                    @endif

                    @if (in_array($type, ['all', 'clinics'], true))
                        <section class="mb-8">
                            <div class="mb-4 flex items-end justify-between gap-3">
                                <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.clinics') }}</h2>
                                <p class="text-sm text-ink-500">{{ __('discover.search.results', ['count' => $clinics->count()]) }}</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                @forelse ($clinics as $clinic)
                                    <x-clinic-card :clinic="$clinic"/>
                                @empty
                                    @if ($type === 'clinics')
                                        <x-card class="md:col-span-2 lg:col-span-3"><x-empty-state :message="__('discover.search.empty')"/></x-card>
                                    @endif
                                @endforelse
                            </div>
                        </section>
                    @endif

                    @if (in_array($type, ['all', 'services'], true))
                        <section class="mb-8">
                            <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('discover.nav.services') }}</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @forelse ($services as $service)
                                    <a href="{{ route('services.show', $service) }}" class="card p-4 hover:ring-2 hover:ring-primary-200">
                                        <div class="flex items-start gap-3">
                                            <span class="grid size-10 place-items-center rounded-full bg-primary-50 text-primary-700">
                                                <x-icon :name="$service->uiIcon()" class="size-5"/>
                                            </span>
                                            <span>
                                                <h3 class="font-semibold text-ink-900">{{ $service->name }}</h3>
                                                @php $from = $service->clinicServices->map->effectivePrice()->filter(fn ($price) => $price > 0)->min(); @endphp
                                                @if ($from)
                                                    <p class="mt-1 text-sm text-primary-700">{{ __('discover.from_price', ['price' => number_format((float) $from)]) }}</p>
                                                @endif
                                            </span>
                                        </div>
                                    </a>
                                @empty
                                    @if ($type === 'services')
                                        <x-card class="sm:col-span-2 lg:col-span-3"><x-empty-state :message="__('discover.search.empty')"/></x-card>
                                    @endif
                                @endforelse
                            </div>
                        </section>
                    @endif

                    @if (in_array($type, ['all', 'offers'], true))
                        <section class="mb-8">
                            <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('discover.nav.offers') }}</h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @forelse ($offers as $offer)
                                    <a href="{{ route('offers.show', $offer) }}" class="card p-4 hover:ring-2 hover:ring-accent-200">
                                        <x-badge tone="accent">{{ $offer->category?->label() }}</x-badge>
                                        <h3 class="mt-2 font-semibold text-ink-900">{{ $offer->title }}</h3>
                                        @if ($offer->offer_price)
                                            <p class="mt-2 text-sm font-semibold text-ink-900">
                                                {{ number_format((float) $offer->offer_price) }} {{ __('common.currency') }}
                                            </p>
                                        @endif
                                        <x-rating class="mt-2" :average="$offer->clinic?->ratingAverage()" :count="$offer->clinic?->ratingCount() ?? 0"/>
                                    </a>
                                @empty
                                    @if ($type === 'offers')
                                        <x-card class="sm:col-span-2 lg:col-span-3"><x-empty-state :message="__('discover.search.empty')"/></x-card>
                                    @endif
                                @endforelse
                            </div>
                        </section>
                    @endif

                    @if (in_array($type, ['all', 'labs'], true))
                        <section>
                            <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('discover.nav.labs') }}</h2>
                            <div class="grid gap-4 md:grid-cols-2">
                                @forelse ($labs as $test)
                                    <a href="{{ route('labs.tests.show', $test) }}" class="card flex items-start justify-between gap-3 p-4">
                                        <span>
                                            <span class="block font-semibold text-ink-900">{{ $test->name }}</span>
                                            <x-rating class="mt-1" :average="null" :count="0"/>
                                        </span>
                                        <span class="text-sm font-semibold text-primary-700">
                                            {{ number_format((float) $test->suggested_price) }} {{ __('common.currency') }}
                                        </span>
                                    </a>
                                @empty
                                    @if ($type === 'labs')
                                        <x-card class="md:col-span-2"><x-empty-state :message="__('discover.search.empty')"/></x-card>
                                    @endif
                                @endforelse
                            </div>
                        </section>
                    @endif

                    @if ($type === 'all' && $doctors->isEmpty() && $clinics->isEmpty() && $services->isEmpty() && $offers->isEmpty() && $labs->isEmpty())
                        <x-card>
                            <x-empty-state :message="__('discover.search.empty')"/>
                        </x-card>
                    @endif
                @endif
            @endfragment
        </div>
    </div>
</x-layouts.public>
