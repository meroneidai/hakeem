<x-layouts.public :title="$heroTitle" :description="$seoDescription">
    @guest
        @if ($signupCampaign)
            <div class="border-b border-accent-200 bg-accent-50">
                <div class="mx-auto flex max-w-6xl items-center gap-2 px-3 py-2 min-[390px]:gap-3 min-[390px]:px-4 sm:py-2.5">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-accent-900 sm:text-sm">{{ $signupCampaign['headline'] }}</p>
                        <p class="mt-0.5 line-clamp-1 text-[11px] leading-snug text-accent-800/90 sm:line-clamp-none sm:text-sm">{{ $signupCampaign['body'] }}</p>
                    </div>
                    <x-button :href="route('register')" variant="accent" size="sm" class="shrink-0 px-2.5 py-1.5 text-xs sm:px-3 sm:text-sm">
                        {{ __('account.loyalty.claim_short') }}
                    </x-button>
                </div>
            </div>
        @endif
    @endguest

    <section class="hero-stage px-3 pb-8 pt-5 min-[390px]:px-4 sm:pb-16 sm:pt-12">
        <x-aurora-blobs/>
        <div class="relative mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:gap-10">
            <div class="text-center lg:text-start">
                <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-primary-800 shadow-sm ring-1 ring-primary-200/80 backdrop-blur">
                    <x-icon name="sparkles" class="size-3.5"/>
                    {{ __('common.app_tagline') }}
                </p>
                <h1 class="text-[1.45rem] font-bold leading-snug tracking-tight text-ink-900 min-[390px]:text-[1.65rem] sm:text-5xl sm:leading-tight">{{ $heroTitle }}</h1>
                <p class="mx-auto mt-2.5 max-w-2xl text-sm leading-6 text-ink-600 sm:mt-4 sm:text-base lg:mx-0">{{ $heroSubtitle }}</p>

                <form method="GET"
                      action="{{ route('search') }}"
                      x-ref="anchor"
                      class="hero-search home-panel-float mx-auto mt-5 w-full max-w-xl p-2 sm:mt-8 sm:p-3 lg:mx-0 lg:max-w-none"
                      x-data="headerSearch(@js(url('/api/v1/search')))"
                      @click.outside="open = false"
                      @keydown.escape.window="open = false">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute inset-y-0 start-3.5 flex items-center text-primary-600 sm:start-4">
                                <x-icon name="search" class="size-5"/>
                            </span>
                            <input name="q"
                                   type="search"
                                   enterkeyhint="search"
                                   inputmode="search"
                                   x-model="q"
                                   @input="onInput()"
                                   @focus="onFocus()"
                                   value="{{ $search['q'] ?? '' }}"
                                   placeholder="{{ __('discover.search_placeholder') }}"
                                   class="field-input min-h-12 w-full rounded-2xl border-0 bg-ink-50 py-3.5 ps-11 text-base leading-normal focus:bg-white focus:ring-0 sm:ps-12"
                                   style="font-size: 16px;"
                                   autocomplete="off"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false">
                        </div>
                        <x-button variant="accent" size="lg" class="min-h-12 w-full shrink-0 rounded-2xl px-6 sm:w-auto sm:px-8">
                            <x-icon name="search" class="size-4"/>
                            {{ __('discover.search_doctors') }}
                        </x-button>
                    </div>
                    <div x-ref="results"
                         x-cloak
                         x-show="open && (loading || results)"
                         x-transition
                         class="hero-search-results rounded-2xl border border-ink-100 bg-white p-2 text-start text-sm shadow-[0_12px_40px_rgba(15,42,95,0.12)]">
                        <p x-show="loading" class="px-2 py-2 text-ink-500">{{ __('discover.search.live') }}…</p>
                        <template x-if="results && !hasHits && !loading">
                            <p class="px-2 py-2 text-ink-500">{{ __('discover.search.empty') }}</p>
                        </template>
                        <div class="grid gap-1.5">
                            <template x-for="item in (results?.doctors || [])" :key="'d'+item.slug">
                                <a :href="item.url" class="flex items-center justify-between rounded-xl bg-primary-50 px-3 py-2.5 hover:bg-primary-100">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-ink-900" x-text="item.name"></span>
                                        <span class="block truncate text-xs text-ink-500" x-text="item.specialty"></span>
                                    </span>
                                    <span class="ms-2 shrink-0 text-xs font-semibold text-accent-600">{{ __('discover.nav.doctors') }}</span>
                                </a>
                            </template>
                            <template x-for="item in (results?.clinics || [])" :key="'c'+item.slug">
                                <a :href="item.url" class="flex items-center justify-between rounded-xl bg-success-50 px-3 py-2.5 hover:bg-success-50/80">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-ink-900" x-text="item.name"></span>
                                        <span class="block truncate text-xs text-ink-500" x-text="item.city"></span>
                                    </span>
                                    <span class="ms-2 shrink-0 text-xs font-semibold text-success-700">{{ __('discover.nav.clinics') }}</span>
                                </a>
                            </template>
                            <template x-for="item in (results?.services || [])" :key="'s'+item.slug">
                                <a :href="item.url" class="rounded-xl bg-accent-50 px-3 py-2.5 font-medium text-ink-900 hover:bg-accent-100">
                                    <span class="block truncate" x-text="item.name"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </form>
                @if ($specialties->isNotEmpty())
                    <div class="mt-5 flex gap-2 overflow-x-auto pb-1 text-sm [-ms-overflow-style:none] [scrollbar-width:none] lg:mt-6 lg:flex-wrap lg:overflow-visible [&::-webkit-scrollbar]:hidden">
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

            <div class="hero-search-scene relative mx-auto w-full max-w-[16rem] sm:max-w-xs lg:max-w-md">
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

    <main class="mx-auto max-w-6xl space-y-6 px-3 pb-10 pt-2 min-[390px]:px-4 sm:space-y-10 sm:pb-14 sm:pt-4">
        @if ($serviceTypes->isNotEmpty())
            <section class="home-panel-soft p-4 sm:p-6">
                <h2 class="mb-3 text-center text-lg font-semibold text-ink-900 sm:mb-5 sm:text-xl">{{ __('discover.services') }}</h2>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4">
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
                        <a href="{{ route('services.show', $type) }}" class="group flex flex-col items-center gap-2 rounded-[1.125rem] bg-white p-3 text-center ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)] sm:gap-3 sm:rounded-[1.25rem] sm:p-4">
                            <span class="grid size-14 place-items-center overflow-hidden rounded-full ring-4 {{ $tone }} sm:size-20 sm:ring-8">
                                @if ($type->image_path)
                                    <x-media :src="\App\Support\PublicImage::url($type->image_path)" :alt="$type->name" class="size-14 rounded-full sm:size-20"/>
                                @else
                                    <x-icon :name="$type->uiIcon()" class="size-6 sm:size-8"/>
                                @endif
                            </span>
                            <span>
                                <span class="block text-xs font-semibold text-ink-900 sm:text-sm">{{ $type->name }}</span>
                                @if ($type->description)
                                    <span class="mt-1 hidden text-xs text-ink-500 sm:block">{{ \Illuminate\Support\Str::limit($type->description, 48) }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                    <a href="{{ route('offers.index') }}" class="group flex flex-col items-center gap-2 rounded-[1.125rem] bg-white p-3 text-center ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)] sm:gap-3 sm:rounded-[1.25rem] sm:p-4">
                        <span class="grid size-14 place-items-center rounded-full bg-accent-50 text-base font-bold text-accent-600 ring-4 ring-accent-100 sm:size-20 sm:text-lg sm:ring-8">%</span>
                        <span class="text-xs font-semibold text-ink-900 sm:text-sm">{{ __('discover.nav.offers') }}</span>
                    </a>
                </div>
            </section>
        @endif

        @if ($specialties->isNotEmpty())
            <section class="home-panel px-4 pb-5 pt-5 sm:p-6">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold leading-snug text-ink-900">{{ __('discover.specialties') }}</h2>
                    <a href="{{ route('specialties.index') }}" class="shrink-0 text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail home-rail-pages" aria-label="{{ __('discover.specialties') }}">
                        @foreach ($specialties->take(12)->chunk(6) as $page)
                            <div class="home-rail-page">
                                @foreach ($page as $specialty)
                                    <a href="{{ route('specialties.show', $specialty) }}" class="home-rail-specialty">
                                        <x-media
                                            :src="\App\Support\PublicImage::url($specialty->image_path)"
                                            :alt="$specialty->name"
                                            class="home-rail-specialty__media"
                                        />
                                        <span class="home-rail-specialty__body">
                                            <span class="home-rail-specialty__name">{{ $specialty->name }}</span>
                                            <span class="home-rail-specialty__meta">
                                                {{ __('discover.doctors.count', ['count' => $specialty->doctors_count]) }}
                                            </span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="home-rail-bleed">
            <div class="home-rail">
                <article class="rounded-[1.25rem] bg-gradient-to-br from-primary-500 to-primary-700 p-4 text-white shadow-[0_12px_40px_rgba(30,64,175,0.16)] sm:p-5">
                    <x-icon name="map" class="mb-2 size-5 text-primary-100 sm:mb-3 sm:size-6"/>
                    <h3 class="text-sm font-semibold sm:text-base">{{ __('discover.why.heading') }}</h3>
                    <p class="mt-1.5 text-xs text-primary-100 sm:mt-2 sm:text-sm">{{ __('discover.why.pin') }}</p>
                </article>
                <article class="rounded-[1.25rem] bg-teal-500 p-4 text-white shadow-[0_4px_20px_rgba(15,42,95,0.08)] sm:p-5">
                    <x-icon name="beaker" class="mb-2 size-5 text-teal-50 sm:mb-3 sm:size-6"/>
                    <h3 class="text-sm font-semibold sm:text-base">{{ __('labs.packages') }}</h3>
                    <p class="mt-1.5 text-xs text-teal-50 sm:mt-2 sm:text-sm">{{ __('discover.why.labs') }}</p>
                </article>
                <article class="rounded-[1.25rem] bg-accent-500 p-4 text-white shadow-[0_4px_20px_rgba(15,42,95,0.08)] sm:p-5">
                    <x-icon name="megaphone" class="mb-2 size-5 text-accent-100 sm:mb-3 sm:size-6"/>
                    <h3 class="text-sm font-semibold sm:text-base">{{ __('discover.nav.offers') }}</h3>
                    <p class="mt-1.5 text-xs text-accent-50 sm:mt-2 sm:text-sm">{{ __('discover.why.offers') }}</p>
                </article>
            </div>
        </section>

        @if ($doctors->isNotEmpty())
            <section class="home-panel-soft p-4 sm:p-6">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.doctors') }}</h2>
                    <a href="{{ route('doctors.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail">
                        @foreach ($doctors as $doctor)
                            <x-doctor-card :doctor="$doctor" class="h-full"/>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($clinics->isNotEmpty())
            <section class="home-panel p-4 sm:p-6">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.clinics') }}</h2>
                    <a href="{{ route('clinics.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail">
                        @foreach ($clinics as $clinic)
                            <x-clinic-card :clinic="$clinic" class="h-full"/>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($governorates->flatMap->cities->isNotEmpty())
            <section>
                <div class="mb-3 flex items-end justify-between gap-3">
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
            <section class="home-panel-teal p-4 sm:p-6">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('labs.packages') }}</h2>
                    <a href="{{ route('labs.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail">
                        @foreach ($packages as $package)
                            <a href="{{ Route::has('labs.packages.show') ? route('labs.packages.show', $package) : route('labs.index') }}" class="overflow-hidden rounded-[1.125rem] bg-white ring-1 ring-ink-200 transition hover:shadow-[0_12px_40px_rgba(30,64,175,0.10)]">
                                @if ($package->image_path)
                                    <x-media
                                        :src="\App\Support\PublicImage::url($package->image_path)"
                                        :alt="$package->name"
                                        class="h-32 w-full sm:h-36"
                                    />
                                @else
                                    <div class="grid h-28 place-items-center bg-success-50 text-success-700 sm:h-28">
                                        <x-icon name="beaker" class="size-10"/>
                                    </div>
                                @endif
                                <div class="p-3 sm:p-4">
                                    @if ($package->discountPercent())
                                        <x-badge tone="accent">{{ $package->discountPercent() }}%</x-badge>
                                    @endif
                                    <h3 class="mt-2 line-clamp-2 text-sm font-semibold text-ink-900 sm:text-base">{{ $package->name }}</h3>
                                    <p class="mt-2 text-sm font-semibold text-ink-900">
                                        {{ number_format((float) $package->package_price) }} {{ __('common.currency') }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($promotions->isNotEmpty())
            <section class="home-panel-warm p-4 sm:p-6">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.offers') }}</h2>
                    <a href="{{ route('offers.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail">
                        @foreach ($promotions as $promotion)
                            <a href="{{ route('offers.show', $promotion) }}" class="overflow-hidden rounded-[1.125rem] bg-white ring-1 ring-ink-200 transition hover:ring-accent-400">
                                @if ($promotion->banner_image_path)
                                    <x-media
                                        :src="\App\Support\PublicImage::url($promotion->banner_image_path)"
                                        :alt="$promotion->title"
                                        class="h-32 w-full sm:h-36"
                                    />
                                @else
                                    <div class="grid h-28 place-items-center bg-accent-50 text-accent-600 sm:h-28">
                                        <x-icon name="megaphone" class="size-10"/>
                                    </div>
                                @endif
                                <div class="p-3 sm:p-4">
                                    <h3 class="line-clamp-2 text-sm font-semibold text-ink-900 sm:text-base">{{ $promotion->title }}</h3>
                                    @if ($promotion->offer_price)
                                        <p class="mt-2 text-sm font-semibold text-ink-900">
                                            {{ number_format((float) $promotion->offer_price) }} {{ __('common.currency') }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (($articles ?? collect())->isNotEmpty() && Route::has('library.index'))
            <section class="home-panel p-4 sm:p-6">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.nav.library') }}</h2>
                    <a href="{{ route('library.index') }}" class="text-sm font-medium text-primary-600">{{ __('common.view') }}</a>
                </div>
                <div class="home-rail-bleed">
                    <div class="home-rail">
                        @foreach ($articles as $article)
                            <a href="{{ Route::has('library.show') ? route('library.show', $article) : route('library.index') }}" class="rounded-[1.125rem] bg-primary-50/70 p-4 transition hover:bg-primary-50">
                                <p class="text-xs font-medium text-primary-700">{{ $article->category->label() }}</p>
                                <h3 class="mt-1 line-clamp-2 text-sm font-semibold text-ink-900 sm:text-base">{{ $article->title }}</h3>
                                @if ($article->excerpt)
                                    <p class="mt-2 line-clamp-2 text-sm text-ink-500">{{ $article->excerpt }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="home-panel-soft p-4 sm:p-6">
            <h2 class="mb-3 text-lg font-semibold text-ink-900 sm:mb-4">{{ __('discover.how.heading') }}</h2>
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4">
                <article class="rounded-[1.125rem] bg-white p-3.5 ring-1 ring-ink-200 sm:p-4">
                    <span class="mb-2 grid size-8 place-items-center rounded-full bg-primary-500 text-sm font-bold text-white sm:size-9">1</span>
                    <p class="text-sm font-medium text-ink-800">{{ __('discover.how.search') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-3.5 ring-1 ring-ink-200 sm:p-4">
                    <span class="mb-2 grid size-8 place-items-center rounded-full bg-primary-700 text-sm font-bold text-white sm:size-9">2</span>
                    <p class="text-sm font-medium text-ink-800">{{ __('discover.how.compare') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-3.5 ring-1 ring-ink-200 sm:p-4">
                    <span class="mb-2 grid size-8 place-items-center rounded-full bg-accent-500 text-sm font-bold text-white sm:size-9">3</span>
                    <p class="text-sm font-medium text-ink-800">{{ __('discover.how.book') }}</p>
                </article>
                <article class="rounded-[1.125rem] bg-white p-3.5 ring-1 ring-ink-200 sm:p-4">
                    <span class="mb-2 grid size-8 place-items-center rounded-full bg-teal-500 text-sm font-bold text-white sm:size-9">4</span>
                    <p class="text-sm font-medium text-ink-800">{{ __('discover.how.visit') }}</p>
                </article>
            </div>
        </section>
    </main>
</x-layouts.public>
