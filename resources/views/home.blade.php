<x-layouts.public :title="$heroTitle" :description="$seoDescription">
    @guest
        @if ($signupCampaign)
            <div class="border-b border-accent-200 bg-accent-50 px-4 py-3">
                <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-semibold text-accent-800">{{ $signupCampaign['headline'] }}</p>
                        <p class="mt-0.5 text-sm text-accent-700">{{ $signupCampaign['body'] }}</p>
                    </div>
                    <x-button :href="route('register')" variant="accent" size="sm" class="shrink-0">{{ __('account.loyalty.claim') }}</x-button>
                </div>
            </div>
        @endif
    @endguest

    <section class="hero-stage px-4 pb-16 pt-10 sm:pb-20 sm:pt-14">
        <x-aurora-blobs/>
        <div class="relative mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="text-center lg:text-start">
                <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-primary-800 shadow-sm ring-1 ring-primary-200/80 backdrop-blur">
                    <x-icon name="sparkles" class="size-3.5"/>
                    {{ __('common.app_tagline') }}
                </p>
                <h1 class="text-3xl font-bold tracking-tight text-ink-900 sm:text-5xl">{{ $heroTitle }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-base text-ink-600 lg:mx-0">{{ $heroSubtitle }}</p>

                <form method="GET" action="{{ route('search') }}" class="home-panel-float mx-auto mt-8 p-2 sm:p-3 lg:mx-0"
                      x-data="headerSearch(@js(url('/api/v1/search')))">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute inset-y-0 start-4 flex items-center text-primary-600">
                                <x-icon name="search" class="size-5"/>
                            </span>
                            <input name="q"
                                   type="search"
                                   enterkeyhint="search"
                                   x-model="q"
                                   @input="onInput()"
                                   @focus="open = true"
                                   value="{{ $search['q'] ?? '' }}"
                                   placeholder="{{ __('discover.search_placeholder') }}"
                                   class="field-input min-h-12 rounded-2xl border-0 bg-ink-50 py-3.5 ps-12 text-base focus:bg-white focus:ring-0"
                                   autocomplete="off">
                        </div>
                        <x-button variant="accent" size="lg" class="min-h-12 shrink-0 rounded-2xl px-8">
                            <x-icon name="search" class="size-4"/>
                            {{ __('discover.search_doctors') }}
                        </x-button>
                    </div>
                    <div x-cloak x-show="open && (loading || results)" class="mt-3 border-t border-ink-100 pt-3 text-start text-sm">
                        <p x-show="loading" class="px-2 py-2 text-ink-500">{{ __('discover.search.live') }}…</p>
                        <template x-if="results && !hasHits && !loading">
                            <p class="px-2 py-2 text-ink-500">{{ __('discover.search.empty') }}</p>
                        </template>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <template x-for="item in (results?.doctors || [])" :key="'d'+item.slug">
                                <a :href="item.url" class="flex items-center justify-between rounded-xl bg-primary-50 px-3 py-2 hover:bg-primary-100">
                                    <span>
                                        <span class="block font-medium text-ink-900" x-text="item.name"></span>
                                        <span class="block text-xs text-ink-500" x-text="item.specialty"></span>
                                    </span>
                                    <span class="text-xs font-semibold text-accent-600">{{ __('discover.nav.doctors') }}</span>
                                </a>
                            </template>
                            <template x-for="item in (results?.clinics || [])" :key="'c'+item.slug">
                                <a :href="item.url" class="flex items-center justify-between rounded-xl bg-success-50 px-3 py-2 hover:bg-success-50/80">
                                    <span>
                                        <span class="block font-medium text-ink-900" x-text="item.name"></span>
                                        <span class="block text-xs text-ink-500" x-text="item.city"></span>
                                    </span>
                                    <span class="text-xs font-semibold text-success-700">{{ __('discover.nav.clinics') }}</span>
                                </a>
                            </template>
                            <template x-for="item in (results?.services || [])" :key="'s'+item.slug">
                                <a :href="item.url" class="rounded-xl bg-accent-50 px-3 py-2 font-medium text-ink-900 hover:bg-accent-100">
                                    <span x-text="item.name"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </form>

                @if ($specialties->isNotEmpty())
                    <div class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm [-ms-overflow-style:none] [scrollbar-width:none] lg:flex-wrap lg:overflow-visible [&::-webkit-scrollbar]:hidden">
                        <span class="shrink-0 self-center text-xs font-medium text-ink-500">{{ __('discover.popular') }}</span>
                        @foreach ($specialties->take(6) as $specialty)
                            <a href="{{ route('search', ['q' => $specialty->name, 'specialty' => $specialty->slug]) }}"
                               class="shrink-0 rounded-full bg-white/90 px-3 py-1.5 text-ink-700 shadow-sm ring-1 ring-ink-200 hover:ring-primary-400">
                                {{ $specialty->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="relative mx-auto w-full max-w-[16rem] sm:max-w-xs lg:max-w-md">
                <div class="absolute -inset-6 rounded-[2.5rem] bg-white/30 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-[1.75rem] bg-white/70 p-4 shadow-[0_12px_40px_rgba(30,64,175,0.10)] ring-1 ring-white/80 backdrop-blur-sm sm:p-6">
                    <x-care-scene class="h-auto w-full"/>
                    @if ($doctors->isNotEmpty())
                        <div class="mt-3 flex items-center justify-center gap-3">
                            <div class="flex">
                                @foreach ($doctors->take(4) as $doctor)
                                    <x-media
                                        :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                                        :alt="$doctor->name"
                                        class="{{ $loop->first ? '' : '-ms-2' }} size-8 rounded-full ring-2 ring-white sm:size-9"
                                    />
                                @endforeach
                            </div>
                            <p class="text-xs font-medium text-primary-800 sm:text-sm">{{ __('discover.hero_scene') }}</p>
                        </div>
                    @else
                        <p class="mt-3 text-center text-sm font-medium text-primary-800">{{ __('discover.hero_scene') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <x-hero-waves/>
    </section>

    @if (! empty($homeBody) && ! \App\Support\SafeHtml::isEmpty($homeBody))
        <div class="mx-auto max-w-3xl px-4 py-8">
            <x-card>
                <div class="rich-content text-sm leading-8 text-ink-700">{!! \App\Support\SafeHtml::render($homeBody) !!}</div>
            </x-card>
        </div>
    @endif

    <main class="mx-auto max-w-6xl space-y-12 px-4 pb-16">
        @if ($serviceTypes->isNotEmpty())
            <section class="home-panel-soft p-6 sm:p-8">
                <h2 class="mb-6 text-center text-xl font-semibold text-ink-900">{{ __('discover.services') }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($serviceTypes as $index => $type)
                        @php
                            $tones = [
                                'bg-primary-50 text-primary-600 ring-primary-100',
                                'bg-primary-50 text-primary-700 ring-primary-100',
                                'bg-teal-50 text-teal-700 ring-teal-100',
                                'bg-primary-50 text-primary-600 ring-primary-100',
                            ];
                            $tone = $tones[$index % count($tones)];
                        @endphp
                        <a href="{{ route('services.show', $type) }}" class="group flex flex-col items-center gap-3 rounded-[1.25rem] bg-white p-4 text-center ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)]">
                            <span class="grid size-20 place-items-center overflow-hidden rounded-full ring-8 {{ $tone }}">
                                @if ($type->image_path)
                                    <x-media :src="\App\Support\PublicImage::url($type->image_path)" :alt="$type->name" class="size-20 rounded-full"/>
                                @else
                                    <x-icon :name="$type->uiIcon()" class="size-8"/>
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">{{ $type->name }}</span>
                                @if ($type->description)
                                    <span class="mt-1 block text-xs text-ink-500">{{ \Illuminate\Support\Str::limit($type->description, 48) }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                    <a href="{{ route('offers.index') }}" class="group flex flex-col items-center gap-3 rounded-[1.25rem] bg-white p-4 text-center ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)]">
                        <span class="grid size-20 place-items-center rounded-full bg-accent-50 text-lg font-bold text-accent-600 ring-8 ring-accent-100">%</span>
                        <span class="text-sm font-semibold text-ink-900">{{ __('discover.nav.offers') }}</span>
                    </a>
                </div>
            </section>
        @endif

        @if ($specialties->isNotEmpty())
            <section class="home-panel p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.specialties') }}</h2>
                    <a href="{{ route('specialties.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($specialties->take(8) as $specialty)
                        <a href="{{ route('specialties.show', $specialty) }}"
                           class="flex items-center gap-3 overflow-hidden rounded-[1.125rem] bg-white p-2.5 ring-1 ring-ink-200 transition hover:ring-primary-300">
                            <x-media
                                :src="\App\Support\PublicImage::url($specialty->image_path)"
                                :alt="$specialty->name"
                                class="size-14 rounded-xl"
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-ink-800">{{ $specialty->name }}</span>
                                <span class="mt-0.5 block text-xs font-normal text-ink-400">
                                    {{ __('discover.doctors.count', ['count' => $specialty->doctors_count]) }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-[1.25rem] bg-gradient-to-br from-primary-500 to-primary-700 p-5 text-white shadow-[0_12px_40px_rgba(30,64,175,0.16)]">
                <x-icon name="map" class="mb-3 size-6 text-primary-100"/>
                <h3 class="font-semibold">{{ __('discover.why.heading') }}</h3>
                <p class="mt-2 text-sm text-primary-100">{{ __('discover.why.pin') }}</p>
            </article>
            <article class="rounded-[1.25rem] bg-teal-500 p-5 text-white shadow-[0_4px_20px_rgba(15,42,95,0.08)]">
                <x-icon name="beaker" class="mb-3 size-6 text-teal-50"/>
                <h3 class="font-semibold">{{ __('labs.packages') }}</h3>
                <p class="mt-2 text-sm text-teal-50">{{ __('discover.why.labs') }}</p>
            </article>
            <article class="rounded-[1.25rem] bg-accent-500 p-5 text-white shadow-[0_4px_20px_rgba(15,42,95,0.08)]">
                <x-icon name="megaphone" class="mb-3 size-6 text-accent-100"/>
                <h3 class="font-semibold">{{ __('discover.nav.offers') }}</h3>
                <p class="mt-2 text-sm text-accent-50">{{ __('discover.why.offers') }}</p>
            </article>
        </section>

        @if ($doctors->isNotEmpty())
            <section class="home-panel-soft p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.doctors') }}</h2>
                    <a href="{{ route('doctors.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($doctors as $doctor)
                        <x-doctor-card :doctor="$doctor"/>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($clinics->isNotEmpty())
            <section class="home-panel p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.clinics') }}</h2>
                    <a href="{{ route('clinics.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($clinics as $clinic)
                        <x-clinic-card :clinic="$clinic"/>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($governorates->flatMap->cities->isNotEmpty())
            <section>
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.cities') }}</h2>
                    <a href="{{ route('cities.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap sm:overflow-visible [&::-webkit-scrollbar]:hidden">
                    @foreach ($governorates->flatMap->cities->take(12) as $city)
                        <a href="{{ route('cities.show', $city) }}" class="shrink-0 rounded-full bg-primary-50 px-3 py-1.5 text-sm font-medium text-primary-800 ring-1 ring-primary-200 hover:bg-primary-100">
                            {{ $city->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($packages->isNotEmpty())
            <section class="home-panel-teal p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('labs.packages') }}</h2>
                    <a href="{{ route('labs.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach ($packages as $package)
                        <a href="{{ Route::has('labs.packages.show') ? route('labs.packages.show', $package) : route('labs.index') }}" class="overflow-hidden rounded-[1.125rem] bg-white ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)]">
                            @if ($package->image_path)
                                <x-media
                                    :src="\App\Support\PublicImage::url($package->image_path)"
                                    :alt="$package->name"
                                    class="h-36 w-full"
                                />
                            @else
                                <div class="grid h-28 place-items-center bg-success-50 text-success-700">
                                    <x-icon name="beaker" class="size-10"/>
                                </div>
                            @endif
                            <div class="p-4">
                                @if ($package->discountPercent())
                                    <x-badge tone="accent">{{ $package->discountPercent() }}%</x-badge>
                                @endif
                                <h3 class="mt-2 font-semibold text-ink-900">{{ $package->name }}</h3>
                                <p class="mt-3 text-sm font-semibold text-ink-900">
                                    {{ number_format((float) $package->package_price) }} {{ __('common.currency') }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($promotions->isNotEmpty())
            <section class="home-panel-warm p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.offers') }}</h2>
                    <a href="{{ route('offers.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($promotions as $promotion)
                        <a href="{{ route('offers.show', $promotion) }}" class="overflow-hidden rounded-[1.125rem] bg-white ring-1 ring-ink-200 transition hover:ring-accent-400">
                            @if ($promotion->banner_image_path)
                                <x-media
                                    :src="\App\Support\PublicImage::url($promotion->banner_image_path)"
                                    :alt="$promotion->title"
                                    class="h-36 w-full"
                                />
                            @else
                                <div class="grid h-28 place-items-center bg-accent-50 text-accent-600">
                                    <x-icon name="megaphone" class="size-10"/>
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="font-semibold text-ink-900">{{ $promotion->title }}</h3>
                                @if ($promotion->offer_price)
                                    <p class="mt-3 text-sm font-semibold text-ink-900">
                                        {{ number_format((float) $promotion->offer_price) }} {{ __('common.currency') }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($articles ?? collect())->isNotEmpty() && Route::has('library.index'))
            <section class="home-panel p-6 sm:p-8">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.library') }}</h2>
                    <a href="{{ route('library.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach ($articles as $article)
                        <a href="{{ Route::has('library.show') ? route('library.show', $article) : route('library.index') }}" class="rounded-[1.125rem] bg-primary-50/70 p-4 transition hover:bg-primary-50">
                            <p class="text-xs font-medium text-primary-700">{{ $article->category->label() }}</p>
                            <h3 class="mt-1 font-semibold text-ink-900">{{ $article->title }}</h3>
                            @if ($article->excerpt)
                                <p class="mt-2 text-sm text-ink-500">{{ $article->excerpt }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @include('partials.app-download')

        <section class="home-panel-soft p-6 sm:p-8">
            <h2 class="mb-5 text-lg font-semibold text-ink-900">{{ __('discover.how.heading') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-[1.125rem] bg-white p-4 ring-1 ring-ink-200">
                    <span class="mb-2 grid size-9 place-items-center rounded-full bg-primary-500 text-sm font-bold text-white">1</span>
                    <p class="font-medium text-ink-800">{{ __('discover.how.search') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-4 ring-1 ring-ink-200">
                    <span class="mb-2 grid size-9 place-items-center rounded-full bg-primary-700 text-sm font-bold text-white">2</span>
                    <p class="font-medium text-ink-800">{{ __('discover.how.compare') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-4 ring-1 ring-ink-200">
                    <span class="mb-2 grid size-9 place-items-center rounded-full bg-accent-500 text-sm font-bold text-white">3</span>
                    <p class="font-medium text-ink-800">{{ __('discover.how.book') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-4 ring-1 ring-ink-200">
                    <span class="mb-2 grid size-9 place-items-center rounded-full bg-teal-500 text-sm font-bold text-white">4</span>
                    <p class="font-medium text-ink-800">{{ __('discover.how.visit') }}</p>
                </article>
            </div>
        </section>
    </main>
</x-layouts.public>
