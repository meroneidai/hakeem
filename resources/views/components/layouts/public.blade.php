@props([
    'title' => null,
    'robots' => 'index,follow',
    'description' => null,
    'image' => null,
    'jsonLd' => [],
])

@php
    $cart = app(\App\Services\LabCart::class);
    $cartCount = $cart->count();
    $cartKeys = $cart->keys();
    $seo = \App\Support\SeoDocument::make(request(), $title, $description, $robots, $image, 'website', $jsonLd);
    $navServices = $navServices ?? collect();
    $navSpecialties = $navSpecialties ?? collect();
    $support = app(\App\Support\SupportLinks::class);
    $branding = $branding ?? app(\App\Support\Branding::class);
    $agentWeb = $branding->agentChatEnabled('web');
    $agentMobile = $branding->agentChatEnabled('mobile');
    $agentAny = $branding->agentChatAvailable();
@endphp

<x-layouts.base :seo="$seo" body-class="min-h-screen theme-v2" theme-color="#3B82F6">
    <div x-data @close-mega.window="$store.shell.mega = false" class="min-h-screen pb-24 lg:pb-0">
        <header class="relative sticky top-0 z-40 border-b border-ink-200/70 bg-white/85 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <a href="{{ route('home') }}" class="flex min-w-0 shrink items-center gap-2.5">
                    <img
                        src="{{ $branding->logoUrl() }}"
                        alt="{{ $branding->name() }}"
                        @class([
                            'shrink-0 object-contain',
                            'h-9 w-auto max-w-[10rem] sm:h-10 sm:max-w-[12rem]' => ! $branding->usesCustomLogo(),
                            'size-10 rounded-2xl object-cover shadow-sm' => $branding->usesCustomLogo(),
                        ])
                    >
                    @if ($branding->usesCustomLogo())
                        <span class="min-w-0">
                            <span class="block truncate text-base font-bold text-ink-900">{{ $branding->name() }}</span>
                            <span class="hidden truncate text-xs text-ink-500 sm:block">{{ $branding->tagline() }}</span>
                        </span>
                    @endif
                </a>

                <nav class="hidden items-center gap-1 text-sm font-medium text-ink-600 lg:flex">
                    <div class="relative" @mouseenter="$store.shell.mega = true" @mouseleave="$store.shell.mega = false">
                        <button type="button"
                                class="inline-flex items-center gap-1 rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800"
                                @click="$store.shell.mega = ! $store.shell.mega"
                                :aria-expanded="$store.shell.mega">
                            {{ __('discover.nav.services') }}
                            <x-icon name="chevron" class="size-3.5 rotate-90"/>
                        </button>
                    </div>
                    <a href="{{ route('doctors.index') }}" class="rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800">{{ __('discover.nav.doctors') }}</a>
                    <a href="{{ route('clinics.index') }}" class="rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800">{{ __('discover.nav.clinics') }}</a>
                    <a href="{{ route('offers.index') }}" class="rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800">{{ __('discover.nav.offers') }}</a>
                    <a href="{{ route('labs.index') }}" class="rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800">{{ __('discover.nav.labs') }}</a>
                    <a href="{{ route('library.index') }}" class="rounded-lg px-3 py-2 hover:bg-primary-50 hover:text-primary-800">{{ __('discover.nav.library') }}</a>
                </nav>

                <div class="flex-1"></div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <a href="{{ route('labs.cart') }}"
                       class="relative grid size-10 place-items-center rounded-full bg-primary-50 text-primary-700 ring-1 ring-primary-100 transition hover:bg-primary-100 hover:text-primary-800"
                       aria-label="{{ __('labs.cart.heading') }}">
                        <x-icon name="bag" class="size-5"/>
                        <span x-cloak
                              x-show="$store.labCart.count > 0"
                              x-text="$store.labCart.count"
                              class="absolute -top-1 -start-1 grid min-h-4 min-w-4 place-items-center rounded-full bg-accent-500 px-1 text-[10px] font-bold leading-none text-white">{{ $cartCount ?: '' }}</span>
                    </a>
                    <x-locale-switcher/>
                    @auth
                        <x-inbox-bell/>
                        @if (auth()->user()->isInternalStaff())
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('admin.dashboard')" size="sm">{{ __('account.dashboard') }}</x-button>
                            </span>
                        @elseif (auth()->user()->isClinicStaff())
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('clinic.dashboard')" size="sm">{{ __('account.dashboard') }}</x-button>
                            </span>
                        @endif
                        <span class="hidden lg:inline-flex">
                            <x-button :href="route('account.edit')" variant="ghost" size="sm">{{ __('discover.dock.account') }}</x-button>
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                            @csrf
                            <x-button variant="ghost" size="sm">{{ __('common.logout') }}</x-button>
                        </form>
                    @else
                        <x-button :href="route('login')" variant="secondary" size="sm">{{ __('auth.login') }}</x-button>
                        <span class="hidden sm:inline-flex">
                            <x-button :href="route('register')" variant="accent" size="sm">{{ __('discover.nav.book') }}</x-button>
                        </span>
                    @endauth
                </div>
            </div>

            <div x-cloak x-show="$store.shell.mega"
                 @mouseenter="$store.shell.mega = true"
                 @mouseleave="$store.shell.mega = false"
                 class="absolute inset-x-0 top-full z-50 border-b border-ink-200 bg-white shadow-lg max-lg:hidden">
                <div class="mx-auto grid max-w-6xl gap-8 px-4 py-6 lg:grid-cols-2">
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('discover.mega.services') }}</h2>
                            <a href="{{ route('services.index') }}" class="text-xs font-medium text-primary-700">{{ __('discover.mega.all_services') }}</a>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @forelse ($navServices as $type)
                                <a href="{{ route('services.show', $type) }}" class="flex items-start gap-3 rounded-2xl p-3 hover:bg-primary-50">
                                    <span class="grid size-10 place-items-center rounded-full bg-primary-100 text-primary-700">
                                        <x-icon :name="$type->uiIcon()" class="size-5"/>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-ink-900">{{ $type->name }}</span>
                                        @if ($type->description)
                                            <span class="mt-0.5 block text-xs leading-5 text-ink-500">{{ \Illuminate\Support\Str::limit($type->description, 72) }}</span>
                                        @endif
                                    </span>
                                </a>
                            @empty
                                <p class="text-sm text-ink-500">{{ __('discover.services_page.empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('discover.mega.specialties') }}</h2>
                            <a href="{{ route('specialties.index') }}" class="text-xs font-medium text-primary-700">{{ __('discover.mega.all_specialties') }}</a>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($navSpecialties as $specialty)
                                <a href="{{ route('specialties.show', $specialty) }}" class="rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-primary-50 hover:text-primary-800">
                                    {{ $specialty->name }}
                                </a>
                            @endforeach
                            <a href="{{ route('home-care') }}" class="rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-primary-50">{{ __('discover.nav.home_care') }}</a>
                            <a href="{{ route('teleconsultation') }}" class="rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-primary-50">{{ __('discover.nav.teleconsultation') }}</a>
                            <a href="{{ route('cities.index') }}" class="rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-primary-50">{{ __('discover.nav.cities') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div x-cloak x-show="$store.shell.mobile" class="fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-ink-900/40" @click="$store.shell.mobile = false"></div>
            <aside class="absolute inset-y-0 start-0 flex w-[min(22rem,92vw)] flex-col bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2" @click="$store.shell.mobile = false">
                        <img
                            src="{{ $branding->logoUrl() }}"
                            alt="{{ $branding->name() }}"
                            class="h-8 w-auto max-w-[9.5rem] shrink-0 object-contain"
                        >
                    </a>
                    <button type="button" class="grid size-9 place-items-center rounded-full bg-ink-50" @click="$store.shell.mobile = false" aria-label="{{ __('discover.nav.close_menu') }}">
                        <x-icon name="x-mark" class="size-5"/>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-4 pb-28">
                    @guest
                    <div class="mb-4 grid grid-cols-2 gap-2">
                        <a href="{{ $support->whatsappUrl() }}" @if ($support->hasWhatsapp()) target="_blank" rel="noopener" @endif
                           class="flex items-center gap-2 rounded-2xl bg-success-50 px-3 py-3 text-sm font-medium text-success-800">
                            <x-icon name="chat" class="size-4"/>
                            {{ __('discover.dock.whatsapp') }}
                        </a>
                        <a href="{{ $support->phoneUrl() }}" class="flex items-center gap-2 rounded-2xl bg-primary-50 px-3 py-3 text-sm font-medium text-primary-800">
                            <x-icon name="phone" class="size-4"/>
                            {{ __('discover.dock.call') }}
                        </a>
                    </div>
                    @endguest
                    @auth
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('discover.dock.account') }}</p>
                        <div class="mb-4 space-y-1 text-sm">
                            @if (auth()->user()->isInternalStaff())
                                <a class="flex items-center gap-2 rounded-xl bg-primary-50 px-2 py-2 font-medium text-primary-800" href="{{ route('admin.dashboard') }}"><x-icon name="grid" class="size-4"/>{{ __('account.dashboard') }}</a>
                            @elseif (auth()->user()->isClinicStaff())
                                <a class="flex items-center gap-2 rounded-xl bg-primary-50 px-2 py-2 font-medium text-primary-800" href="{{ route('clinic.dashboard') }}"><x-icon name="building" class="size-4"/>{{ __('account.dashboard') }}</a>
                            @endif
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('account.edit') }}"><x-icon name="user" class="size-4 text-ink-400"/>{{ __('discover.dock.account') }}</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-2 py-2 text-start hover:bg-ink-50"><x-icon name="logout" class="size-4 text-ink-400"/>{{ __('common.logout') }}</button>
                            </form>
                        </div>
                    @else
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('discover.mega.services') }}</p>
                        <div class="mb-4 space-y-1">
                            @foreach ($navServices as $type)
                                <a href="{{ route('services.show', $type) }}" class="flex items-center gap-2 rounded-xl px-2 py-2 text-sm hover:bg-primary-50">
                                    <x-icon :name="$type->uiIcon()" class="size-4 text-primary-700"/>
                                    {{ $type->name }}
                                </a>
                            @endforeach
                        </div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('account.profile') }}</p>
                        <div class="mb-4 space-y-1 text-sm">
                            <a class="flex items-center gap-2 rounded-xl bg-primary-50 px-2 py-2 font-medium text-primary-800" href="{{ route('login') }}"><x-icon name="user" class="size-4"/>{{ __('auth.login') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('register') }}"><x-icon name="user" class="size-4 text-ink-400"/>{{ __('auth.register') }}</a>
                        </div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('discover.nav.menu') }}</p>
                        <div class="space-y-1 text-sm">
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('doctors.index') }}"><x-icon name="stethoscope" class="size-4 text-ink-400"/>{{ __('discover.nav.doctors') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('clinics.index') }}"><x-icon name="building" class="size-4 text-ink-400"/>{{ __('discover.nav.clinics') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('offers.index') }}"><x-icon name="megaphone" class="size-4 text-ink-400"/>{{ __('discover.nav.offers') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('labs.index') }}"><x-icon name="beaker" class="size-4 text-ink-400"/>{{ __('discover.nav.labs') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('library.index') }}"><x-icon name="layers" class="size-4 text-ink-400"/>{{ __('discover.nav.library') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('labs.cart') }}">
                                <span class="relative">
                                    <x-icon name="bag" class="size-4 text-ink-400"/>
                                    <span x-cloak x-show="$store.labCart.count > 0" x-text="$store.labCart.count" class="absolute -top-2 -end-2 grid min-w-4 place-items-center rounded-full bg-accent-500 px-1 text-[9px] font-bold text-white">{{ $cartCount ?: '' }}</span>
                                </span>
                                {{ __('labs.cart.heading') }}
                            </a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('home-care') }}"><x-icon name="home" class="size-4 text-ink-400"/>{{ __('discover.nav.home_care') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('teleconsultation') }}"><x-icon name="video" class="size-4 text-ink-400"/>{{ __('discover.nav.teleconsultation') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('complaints.create') }}"><x-icon name="chat" class="size-4 text-ink-400"/>{{ __('pages.complaints.heading') }}</a>
                        </div>
                    @endauth
                </div>
            </aside>
        </div>

        @if (session('status') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4">
                @if (session('status'))
                    <x-alert tone="success">{{ session('status') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert tone="danger">{{ session('error') }}</x-alert>
                @endif
            </div>
        @endif

        @auth
            @php
                $needsProfile = ! auth()->user()->isInternalStaff()
                    && ! ($branding ?? app(\App\Support\Branding::class))->profileComplete(auth()->user());
            @endphp
            @if (session('profile_complete'))
                <div class="border-b border-success-200 bg-success-50 px-4 py-2 text-center text-sm text-success-800">
                    {{ __('account.complete') }}
                </div>
            @elseif ($needsProfile)
                @php
                    $profileGaps = ($branding ?? app(\App\Support\Branding::class))->profileGaps(auth()->user());
                    $firstGap = $profileGaps[0]['label'] ?? null;
                @endphp
                <div class="border-b border-accent-200 bg-accent-50 px-4 py-2 text-center text-sm text-accent-900">
                    <a href="{{ route('account.edit') }}" class="font-medium underline-offset-2 hover:underline">
                        {{ __('account.incomplete') }}
                        @if ($firstGap)
                            <span class="font-semibold">— {{ $firstGap }}</span>
                        @endif
                    </a>
                </div>
            @endif
        @endauth

        {{ $slot }}

        <footer class="footer-stage pt-16 pb-12 text-sm text-primary-100">
            <x-hero-waves footer/>
            <x-aurora-blobs footer/>
            <div class="relative mx-auto grid max-w-6xl gap-8 px-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <img
                        src="{{ $branding->footerLogoUrl() }}"
                        alt="{{ $branding->name() }}"
                        @class([
                            'mb-3 object-contain',
                            'h-12 w-auto max-w-[12rem]' => ! $branding->usesCustomFooterLogo(),
                            'h-11 w-11 rounded-xl object-cover ring-2 ring-white/20' => $branding->usesCustomFooterLogo(),
                        ])
                    >
                    @if ($branding->usesCustomFooterLogo())
                        <p class="text-base font-semibold text-white">{{ $branding->name() }}</p>
                        <p class="mt-2 text-xs text-primary-200">{{ $branding->tagline() }}</p>
                    @else
                        <p class="mt-1 text-xs text-primary-200">{{ $branding->tagline() }}</p>
                    @endif
                    <a href="{{ route('search') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/20 hover:bg-white/20">
                        <x-icon name="search" class="size-3.5"/>
                        {{ __('common.search') }}
                    </a>
                    @if ($branding->social())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($branding->social() as $network => $url)
                                <a href="{{ $url }}" class="text-xs font-medium text-primary-200 hover:text-white" rel="noopener noreferrer" target="_blank">{{ __('admin.branding.networks.'.$network) }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if ($branding->appIosUrl() || $branding->appAndroidUrl())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($branding->appIosUrl())
                                <a href="{{ $branding->appIosUrl() }}" class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20 hover:bg-white/20" target="_blank" rel="noopener">{{ __('discover.app.ios') }}</a>
                            @endif
                            @if ($branding->appAndroidUrl())
                                <a href="{{ $branding->appAndroidUrl() }}" class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20 hover:bg-white/20" target="_blank" rel="noopener">{{ __('discover.app.android') }}</a>
                            @endif
                        </div>
                    @endif
                    <div class="mt-4 space-y-1 text-xs text-primary-100">
                        @if ($support->hasPhone())
                            <a href="{{ $support->phoneUrl() }}" class="block hover:text-white" dir="ltr">{{ $support->telephone() }}</a>
                        @endif
                        @if ($support->email())
                            <a href="mailto:{{ $support->email() }}" class="block hover:text-white" dir="ltr">{{ $support->email() }}</a>
                        @endif
                        @if ($support->hasWhatsapp())
                            <a href="{{ $support->whatsappUrl() }}" class="block hover:text-white" target="_blank" rel="noopener">{{ __('pages.contact.whatsapp') }}</a>
                        @endif
                    </div>
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-primary-200">{{ __('discover.footer.browse') }}</p>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('doctors.index') }}">{{ __('discover.nav.doctors') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('clinics.index') }}">{{ __('discover.nav.clinics') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('specialties.index') }}">{{ __('discover.nav.specialties') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('services.index') }}">{{ __('discover.nav.services') }}</a>
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-primary-200">{{ __('discover.footer.care') }}</p>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('offers.index') }}">{{ __('discover.nav.offers') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('labs.index') }}">{{ __('discover.nav.labs') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('library.index') }}">{{ __('discover.nav.library') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('cities.index') }}">{{ __('discover.nav.cities') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('home-care') }}">{{ __('discover.nav.home_care') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('teleconsultation') }}">{{ __('discover.nav.teleconsultation') }}</a>
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-primary-200">{{ __('discover.footer.legal') }}</p>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('how-it-works') }}">{{ __('pages.how.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('about') }}">{{ __('pages.about.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('contact') }}">{{ __('pages.contact.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('complaints.create') }}">{{ __('pages.complaints.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('help') }}">{{ __('pages.help.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('terms') }}">{{ __('pages.terms.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('privacy') }}">{{ __('pages.privacy.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('cookies') }}">{{ __('pages.cookies.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('cancellation') }}">{{ __('pages.cancellation.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('disclaimer') }}">{{ __('pages.disclaimer.heading') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('accessibility') }}">{{ __('pages.accessibility.heading') }}</a>
                    @env('local')
                        <a class="block text-primary-100 hover:text-white" href="{{ route('admin.login') }}">{{ __('auth.admin_login') }}</a>
                    @endenv
                </div>
            </div>
            <p class="relative mt-10 text-center text-xs text-primary-300">{{ $branding->name() }} — {{ now()->year }}</p>
        </footer>

        @include('partials.cookie-banner')

        @if ($agentAny)
            <div x-data="siteAgent(@js(route('agent.messages')), @js(csrf_token()), {
                     empty: @js(__('agent.empty')),
                     error: @js(__('agent.error')),
                 })"
                 @open-agent.window="openAgent()"
                 @close-agent.window="closeAgent()">
                @if ($agentWeb)
                    <button type="button"
                            @click="toggle()"
                            class="fixed bottom-5 end-5 z-40 hidden size-14 items-center justify-center rounded-full bg-teal-500 text-white shadow-[0_12px_40px_rgba(30,64,175,0.12)] ring-4 ring-teal-50 transition hover:bg-teal-600 lg:flex"
                            :aria-label="open ? @js(__('agent.close')) : @js(__('agent.open'))">
                        <span x-show="!open"><x-icon name="sparkles" class="size-6"/></span>
                        <span x-cloak x-show="open"><x-icon name="x-mark" class="size-6"/></span>
                    </button>
                @endif
                <div x-cloak
                     x-show="open"
                     x-transition.opacity.duration.150ms
                     class="app-sheet fixed inset-0 z-[70] flex h-[100dvh] max-h-[100dvh] flex-col overflow-hidden bg-white lg:inset-auto lg:bottom-24 lg:end-5 lg:h-[34rem] lg:max-h-[34rem] lg:w-[28rem] lg:rounded-3xl lg:border lg:border-ink-200 lg:shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-label="{{ __('agent.title') }}">
                    @include('partials.agent-thread')
                </div>
            </div>
        @endif

        <div x-cloak
             x-show="$store.labCart.toast"
             x-text="$store.labCart.toast"
             class="pointer-events-none fixed bottom-28 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-lg">
        </div>

        <nav class="mobile-dock fixed inset-x-3 bottom-3 z-50 lg:hidden" aria-label="{{ __('discover.dock.label') }}">
            <div class="flex {{ $agentMobile ? 'items-end justify-between' : 'items-center justify-around' }} rounded-[1.75rem] border border-ink-200/80 bg-white/95 px-1.5 py-1.5 shadow-lg backdrop-blur-md">
                <a href="{{ route('home') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                    <x-icon name="home" class="size-5"/>
                    {{ __('discover.dock.home') }}
                </a>
                @auth
                    @if (auth()->user()->isInternalStaff())
                        <a href="{{ route('admin.dashboard') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                            <x-icon name="grid" class="size-5"/>
                            {{ __('account.dashboard') }}
                        </a>
                    @elseif (auth()->user()->isClinicStaff())
                        <a href="{{ route('clinic.dashboard') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                            <x-icon name="building" class="size-5"/>
                            {{ __('account.dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('labs.index') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                            <x-icon name="beaker" class="size-5"/>
                            {{ __('discover.nav.labs') }}
                        </a>
                    @endif
                @else
                    <a href="{{ route('doctors.index') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-primary-700">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-50 text-primary-700">
                            <x-icon name="stethoscope" class="size-4"/>
                        </span>
                        {{ __('discover.nav.doctors') }}
                    </a>
                @endauth
                @if ($agentMobile)
                    <button type="button"
                            @click="$dispatch('open-agent')"
                            class="-mt-7 flex size-14 flex-col items-center justify-center rounded-full bg-teal-500 text-white shadow-[0_12px_40px_rgba(15,42,95,0.16)] ring-4 ring-white"
                            aria-label="{{ __('discover.dock.agent') }}">
                        <x-icon name="sparkles" class="size-6"/>
                    </button>
                @else
                    <a href="{{ route('offers.index') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-accent-700">
                        <span class="grid size-8 place-items-center rounded-full bg-accent-50 text-accent-700">
                            <x-icon name="sparkles" class="size-4"/>
                        </span>
                        {{ __('discover.nav.offers') }}
                    </a>
                @endif
                @auth
                    <a href="{{ route('account.edit') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-primary-700">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-50 text-primary-700">
                            <x-icon name="user" class="size-4"/>
                        </span>
                        {{ __('discover.dock.account') }}
                    </a>
                @else
                    <a href="{{ $support->whatsappUrl() }}"
                       @if ($support->hasWhatsapp()) target="_blank" rel="noopener" @endif
                       class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-success-700">
                        <span class="grid size-8 place-items-center rounded-full bg-success-50 text-success-700">
                            <x-icon name="chat" class="size-4"/>
                        </span>
                        {{ __('discover.dock.whatsapp') }}
                    </a>
                @endauth
                <button type="button"
                        @click="$store.shell.mobile = true"
                        class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                    <x-icon name="bars-3" class="size-5"/>
                    {{ __('discover.dock.more') }}
                </button>
            </div>
        </nav>
    </div>

    @include('partials.public-alpine')
</x-layouts.base>
