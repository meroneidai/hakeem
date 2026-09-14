<x-layouts.base>
    <header class="border-b border-ink-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-xl bg-primary-600 text-xl font-bold text-white">ح</span>
                <span>
                    <span class="block text-base font-bold text-ink-900">{{ __('common.app_name') }}</span>
                    <span class="block text-xs text-ink-500">{{ __('common.app_tagline') }}</span>
                </span>
            </a>

            <div class="flex items-center gap-2">
                <x-locale-switcher/>

                @auth
                    @if (auth()->user()->isInternalStaff())
                        <x-button :href="route('admin.dashboard')" size="sm">{{ __('admin.title') }}</x-button>
                    @elseif (auth()->user()->isClinicStaff())
                        <x-button :href="route('clinic.dashboard')" size="sm">{{ __('clinic.title') }}</x-button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button variant="ghost" size="sm">{{ __('common.logout') }}</x-button>
                    </form>
                @else
                    <x-button :href="route('login')" variant="secondary" size="sm">{{ __('auth.login') }}</x-button>
                    <x-button :href="route('register.clinic')" variant="secondary" size="sm">{{ __('auth.register_clinic') }}</x-button>
                    <x-button :href="route('register')" variant="accent" size="sm">{{ __('auth.register') }}</x-button>
                @endauth
            </div>
        </div>
    </header>

    {{-- Search shell. Wired to real results in Phase 7 (discovery & SEO). --}}
    <section class="bg-gradient-to-b from-primary-50 to-ink-50 px-4 py-12">
        <div class="mx-auto max-w-4xl text-center">
            <h1 class="text-2xl font-bold text-ink-900 sm:text-4xl">{{ __('common.app_tagline') }}</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm text-ink-600 sm:text-base">
                {{ app()->getLocale() === 'ar'
                    ? 'ابحث بالمحافظة والتخصص، واحجز موعدك في خطوات قليلة — عيادة، زيارة منزلية، فيديو، أو تحاليل.'
                    : 'Search by governorate and specialty, then book in a few steps — clinic, home visit, video or lab tests.' }}
            </p>

            <div class="card mt-8 grid gap-3 p-4 text-start sm:grid-cols-[1fr_1fr_auto]">
                <x-select name="governorate" :placeholder="__('admin.cities.governorate')"
                          :options="$governorates->pluck('name', 'slug')->all()"/>
                <x-select name="specialty" :placeholder="__('admin.nav.specialties')"
                          :options="$specialties->pluck('name', 'slug')->all()"/>
                <x-button variant="accent" size="lg" disabled title="Phase 7">
                    <x-icon name="search" class="size-4"/>
                    {{ __('common.search') }}
                </x-button>
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-6xl space-y-10 px-4 py-10">
        <section>
            <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('admin.nav.service_types') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($serviceTypes as $type)
                    <article class="card p-4">
                        <h3 class="font-semibold text-ink-900">{{ $type->name }}</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $type->description }}</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if ($type->is_online)
                                <x-badge tone="primary">{{ __('admin.service_types.is_online') }}</x-badge>
                            @endif
                            @if ($type->requires_patient_address)
                                <x-badge>{{ __('admin.service_types.requires_patient_address') }}</x-badge>
                            @endif
                            @if ($type->is_sensitive)
                                <x-badge tone="warning">{{ __('admin.service_types.is_sensitive') }}</x-badge>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        @if ($featuredSpecialties->isNotEmpty())
            <section>
                <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('admin.nav.specialties') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($featuredSpecialties as $specialty)
                        <span class="card px-3.5 py-2 text-sm font-medium text-ink-700">{{ $specialty->name }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($promotions->isNotEmpty())
            <section>
                <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('admin.promotions.heading') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($promotions as $promotion)
                        <article class="card border-accent-200 p-4">
                            <x-badge tone="accent">{{ __('admin.promotions.statuses.running') }}</x-badge>
                            <h3 class="mt-2 font-semibold text-ink-900">{{ $promotion->title }}</h3>
                            <p class="mt-1 text-sm text-ink-500">
                                {{ $promotion->discount_details ?? ($promotion->discount_type === 'percentage'
                                    ? number_format((float) $promotion->discount_value).'%'
                                    : number_format((float) $promotion->discount_value).' '.__('common.currency')) }}
                            </p>
                            <p class="mt-2 text-xs text-ink-400">
                                {{ $promotion->ends_at->translatedFormat('d M Y') }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section>
            <h2 class="mb-4 text-lg font-semibold text-ink-900">{{ __('admin.nav.governorates') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($governorates as $governorate)
                    <article class="card p-4">
                        <h3 class="text-sm font-semibold text-ink-900">{{ $governorate->name }}</h3>
                        <p class="mt-1 text-xs text-ink-500">
                            {{ $governorate->cities->take(4)->map(fn ($city) => $city->name)->join('، ') }}
                            @if ($governorate->cities->count() > 4)
                                <span class="text-ink-400">+{{ $governorate->cities->count() - 4 }}</span>
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-ink-200 bg-white py-6 text-center text-xs text-ink-400">
        {{ __('common.app_name') }} — {{ now()->year }}
    </footer>
</x-layouts.base>
