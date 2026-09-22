<x-layouts.public :title="$doctor->name" :description="$seoDescription ?? null" :image="$seoImage ?? null" :json-ld="$jsonLd ?? []">
    <x-catalog-hero :title="$doctor->name" :subtitle="$doctor->specialty?->name">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('doctors.index') }}" class="hover:text-primary-700">{{ __('discover.nav.doctors') }}</a>
            @if ($doctor->specialty)
                <span aria-hidden="true">·</span>
                <a href="{{ route('specialties.show', $doctor->specialty) }}" class="hover:text-primary-700">{{ $doctor->specialty->name }}</a>
            @endif
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('book.doctors.create', $doctor)" variant="accent" size="lg" class="rounded-2xl px-6">
                {{ __('discover.book_now') }}
            </x-button>
        </x-slot:actions>

        <div class="mt-6 flex flex-wrap items-start gap-5">
            <x-media
                :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                :alt="$doctor->name"
                class="size-28 rounded-3xl ring-4 ring-white shadow-md sm:size-32"
            />
            <div class="flex min-w-0 flex-1 flex-wrap gap-2">
                <x-rating class="w-full" :average="$doctor->ratingAverage()" :count="$doctor->ratingCount()"/>
                @if ($doctor->years_of_experience)
                    <span class="rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200">
                        {{ __('discover.doctors.years', ['count' => $doctor->years_of_experience]) }}
                    </span>
                @endif
                @if ($doctor->consultation_fee)
                    <span class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-primary-800 ring-1 ring-primary-200">
                        {{ number_format((float) $doctor->consultation_fee) }} {{ __('common.currency') }}
                    </span>
                @endif
                @if ($doctor->credentials)
                    <p class="w-full text-sm text-ink-600">{{ $doctor->credentials }}</p>
                @endif
            </div>
        </div>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8">
        @if ($doctor->bio)
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.doctors.about') }}</h2>
                <x-card><p class="text-sm leading-7 text-ink-600">{{ $doctor->bio }}</p></x-card>
            </section>
        @endif

        @if ($doctor->clinics->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.doctors.locations') }}</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($doctor->clinics as $clinic)
                        <a href="{{ route('clinics.show', $clinic) }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary-50 text-primary-700">
                                <x-icon name="building" class="size-5"/>
                            </span>
                            <span>
                                <span class="block font-semibold text-ink-900">{{ $clinic->name }}</span>
                                @foreach ($clinic->addresses as $address)
                                    <span class="mt-1 block text-sm text-ink-500">
                                        {{ $address->displayName() }}
                                        @if ($address->address_line) — {{ $address->address_line }} @endif
                                    </span>
                                @endforeach
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($doctor->availability->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.doctors.availability') }}</h2>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($doctor->availability as $slot)
                        <article class="card p-4 text-sm">
                            <p class="font-medium text-ink-800">{{ $slot->day_of_week->label() }}</p>
                            <p class="mt-1 text-ink-500">{{ $slot->timeRange() }} · {{ $slot->address?->displayName() }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
