<x-layouts.public :title="$clinic->name" :description="$seoDescription ?? null" :image="$seoImage ?? null" :json-ld="$jsonLd ?? []">
    <x-catalog-hero :title="$clinic->name" :subtitle="$clinic->primaryAddress?->city?->name">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('clinics.index') }}" class="hover:text-primary-700">{{ __('discover.nav.clinics') }}</a>
            @if ($clinic->primaryAddress?->city)
                <span aria-hidden="true">·</span>
                <a href="{{ route('cities.show', $clinic->primaryAddress->city) }}" class="hover:text-primary-700">{{ $clinic->primaryAddress->city->name }}</a>
            @endif
        </x-slot:crumbs>
        <x-slot:actions>
            @if ($clinic->isVerified())
                <x-badge tone="success">{{ __('discover.verified') }}</x-badge>
            @endif
            @if ($clinic->doctors->first())
                <x-button :href="route('book.doctors.create', $clinic->doctors->first())" variant="accent" size="lg" class="rounded-2xl px-6">
                    {{ __('discover.book_now') }}
                </x-button>
            @endif
        </x-slot:actions>

        <div class="mt-6 flex flex-wrap items-start gap-5">
            <x-media
                :src="\App\Support\PublicImage::url($clinic->logo_path)"
                :alt="$clinic->name"
                class="size-28 rounded-3xl ring-4 ring-white shadow-md sm:size-32"
            />
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <x-rating :average="$clinic->ratingAverage()" :count="$clinic->ratingCount()"/>
                @if ($clinic->phone)
                    <a href="tel:{{ $clinic->phone }}" class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200">
                        <x-icon name="phone" class="size-4 text-primary-700"/>
                        {{ $clinic->phone }}
                    </a>
                @endif
                @if ($clinic->doctors->isNotEmpty())
                    <span class="rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200">
                        {{ __('discover.doctors.count', ['count' => $clinic->doctors->count()]) }}
                    </span>
                @endif
            </div>
        </div>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8">
        @if ($clinic->description)
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('pages.about.heading') }}</h2>
                <x-card><p class="text-sm leading-7 text-ink-600">{{ $clinic->description }}</p></x-card>
            </section>
        @endif

        @if ($clinic->addresses->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.doctors.locations') }}</h2>
                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach ($clinic->addresses as $address)
                        <article class="card p-4">
                            <p class="font-semibold text-ink-900">{{ $address->displayName() }}</p>
                            @if ($address->address_line)
                                <p class="mt-1 text-sm text-ink-500">{{ $address->address_line }}</p>
                            @endif
                            @if ($address->city)
                                <a href="{{ route('cities.show', $address->city) }}" class="mt-1 inline-block text-sm text-primary-600">{{ $address->city->name }}</a>
                            @endif
                            @if ($address->schedules->isNotEmpty())
                                <p class="mt-3 text-xs font-medium text-ink-500">{{ __('discover.clinics.hours') }}</p>
                                <ul class="mt-1 space-y-0.5 text-sm text-ink-600">
                                    @foreach ($address->schedules as $schedule)
                                        <li>{{ $schedule->day_of_week->label() }} · {{ $schedule->is_closed ? __('common.none') : $schedule->timeRange() }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($address->hasMapPin())
                                <a href="{{ $address->mapsUrl() }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary-600">
                                    <x-icon name="map-pin" class="size-4"/>
                                    {{ __('discover.clinics.map') }}
                                </a>
                                <iframe
                                    title="{{ $address->displayName() }}"
                                    class="mt-3 h-56 w-full rounded-xl border-0"
                                    loading="lazy"
                                    src="https://maps.google.com/maps?q={{ $address->latitude }},{{ $address->longitude }}&z=15&output=embed"
                                ></iframe>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($clinic->doctors->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.clinics.doctors') }}</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($clinic->doctors as $doctor)
                        <x-doctor-card :doctor="$doctor"/>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($clinic->services->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.nav.services') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($clinic->services as $service)
                        @continue(! $service->serviceType)
                        <a href="{{ route('services.show', $service->serviceType) }}" class="card p-4 hover:ring-2 hover:ring-primary-200">
                            <p class="font-semibold text-ink-900">{{ $service->serviceType->name }}</p>
                            @if ($service->price)
                                <p class="mt-1 text-sm text-ink-500">{{ number_format((float) $service->effectivePrice()) }} {{ __('common.currency') }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($clinic->promotions->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.nav.offers') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($clinic->promotions as $promotion)
                        <a href="{{ route('offers.show', $promotion) }}" class="card p-4 hover:ring-2 hover:ring-accent-200">
                            <p class="font-semibold text-ink-900">{{ $promotion->title }}</p>
                            @if ($promotion->offer_price)
                                <p class="mt-1 text-sm text-ink-500">{{ number_format((float) $promotion->offer_price) }} {{ __('common.currency') }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
