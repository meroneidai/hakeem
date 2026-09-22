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
@endphp

<x-layouts.base :seo="$seo">
    <div x-data @close-mega.window="$store.shell.mega = false" class="min-h-screen pb-24 lg:pb-0">
        <header class="relative sticky top-0 z-40 border-b border-ink-200/80 bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                    @if (($branding ?? null)?->logoUrl())
                        <img src="{{ $branding->logoUrl() }}" alt="{{ __('common.app_name') }}" class="size-10 rounded-2xl object-cover shadow-sm">
                    @else
                        <span class="grid size-10 place-items-center rounded-2xl bg-primary-600 text-xl font-bold text-white shadow-sm">ح</span>
                    @endif
                    <span class="hidden sm:block">
                        <span class="block text-base font-bold text-ink-900">{{ __('common.app_name') }}</span>
                        <span class="block text-xs text-ink-500">{{ ($branding ?? null)?->tagline() ?: __('common.app_tagline') }}</span>
                    </span>
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
                </nav>

                <div class="flex-1"></div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <a href="{{ route('search') }}"
                       class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-2 text-sm font-medium text-primary-800 hover:bg-primary-100"
                       aria-label="{{ __('common.search') }}">
                        <x-icon name="search" class="size-5"/>
                        <span class="hidden sm:inline">{{ __('common.search') }}</span>
                    </a>
                    <a href="{{ route('labs.cart') }}"
                       class="relative grid size-10 place-items-center rounded-full bg-accent-50 text-accent-700 ring-1 ring-accent-100 transition hover:bg-accent-100 hover:text-accent-800"
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
                        @unless (auth()->user()->isInternalStaff())
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('account.edit')" variant="ghost" size="sm">{{ __('account.profile') }}</x-button>
                            </span>
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('appointments.index')" variant="ghost" size="sm">{{ __('booking.my_appointments') }}</x-button>
                            </span>
                        @endunless
                        @if (auth()->user()->isInternalStaff())
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('admin.dashboard')" size="sm">{{ __('admin.title') }}</x-button>
                            </span>
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('account.edit')" variant="ghost" size="sm">{{ __('account.profile') }}</x-button>
                            </span>
                        @elseif (auth()->user()->isClinicStaff())
                            <span class="hidden lg:inline-flex">
                                <x-button :href="route('clinic.dashboard')" size="sm">{{ __('clinic.title') }}</x-button>
                            </span>
                        @endif
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
                    <div>
                        <p class="font-semibold text-ink-900">{{ __('discover.nav.menu') }}</p>
                        <p class="text-xs text-ink-400">{{ __('common.app_tagline') }}</p>
                    </div>
                    <button type="button" class="grid size-9 place-items-center rounded-full bg-ink-50" @click="$store.shell.mobile = false" aria-label="{{ __('discover.nav.close_menu') }}">
                        <x-icon name="x-mark" class="size-5"/>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <form method="GET" action="{{ route('search') }}" class="mb-4">
                        <label class="sr-only" for="mobile-menu-q">{{ __('common.search') }}</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-primary-600">
                                <x-icon name="search" class="size-4"/>
                            </span>
                            <input id="mobile-menu-q"
                                   type="search"
                                   name="q"
                                   enterkeyhint="search"
                                   placeholder="{{ __('discover.search_placeholder') }}"
                                   class="field-input min-h-11 ps-10 text-base">
                        </div>
                    </form>
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
                        @auth
                            <a class="flex items-center gap-2 rounded-xl bg-primary-50 px-2 py-2 font-medium text-primary-800" href="{{ route('account.edit') }}"><x-icon name="user" class="size-4"/>{{ __('account.profile') }}</a>
                            @unless (auth()->user()->isInternalStaff())
                                <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('appointments.index') }}"><x-icon name="calendar" class="size-4 text-ink-400"/>{{ __('booking.my_appointments') }}</a>
                            @endunless
                            @if (auth()->user()->isClinicStaff())
                                <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('clinic.dashboard') }}"><x-icon name="building" class="size-4 text-ink-400"/>{{ __('clinic.title') }}</a>
                            @endif
                            @if (auth()->user()->isInternalStaff())
                                <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('admin.dashboard') }}"><x-icon name="grid" class="size-4 text-ink-400"/>{{ __('admin.title') }}</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-2 py-2 text-start hover:bg-ink-50"><x-icon name="logout" class="size-4 text-ink-400"/>{{ __('common.logout') }}</button>
                            </form>
                        @else
                            <a class="flex items-center gap-2 rounded-xl bg-primary-50 px-2 py-2 font-medium text-primary-800" href="{{ route('login') }}"><x-icon name="user" class="size-4"/>{{ __('auth.login') }}</a>
                            <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('register') }}"><x-icon name="user" class="size-4 text-ink-400"/>{{ __('auth.register') }}</a>
                        @endauth
                    </div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('discover.nav.menu') }}</p>
                    <div class="space-y-1 text-sm">
                        <a class="flex items-center gap-2 rounded-xl bg-ink-50 px-2 py-2 font-medium hover:bg-primary-50" href="{{ route('search') }}"><x-icon name="search" class="size-4 text-primary-700"/>{{ __('common.search') }}</a>
                        <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('doctors.index') }}"><x-icon name="stethoscope" class="size-4 text-ink-400"/>{{ __('discover.nav.doctors') }}</a>
                        <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('clinics.index') }}"><x-icon name="building" class="size-4 text-ink-400"/>{{ __('discover.nav.clinics') }}</a>
                        <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('offers.index') }}"><x-icon name="megaphone" class="size-4 text-ink-400"/>{{ __('discover.nav.offers') }}</a>
                        <a class="flex items-center gap-2 rounded-xl px-2 py-2 hover:bg-ink-50" href="{{ route('labs.index') }}"><x-icon name="beaker" class="size-4 text-ink-400"/>{{ __('discover.nav.labs') }}</a>
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
                <div class="border-b border-accent-200 bg-accent-50 px-4 py-2 text-center text-sm text-accent-900">
                    <a href="{{ route('account.edit') }}" class="font-medium underline-offset-2 hover:underline">{{ __('account.incomplete') }}</a>
                </div>
            @endif
        @endauth

        {{ $slot }}

        <footer class="footer-stage pt-16 pb-12 text-sm text-primary-100">
            <x-hero-waves footer/>
            <x-aurora-blobs footer/>
            <div class="relative mx-auto grid max-w-6xl gap-8 px-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    @if (($branding ?? null)?->logoUrl())
                        <img src="{{ $branding->logoUrl() }}" alt="" class="mb-3 h-11 w-11 rounded-xl object-cover ring-2 ring-white/20">
                    @else
                        <span class="mb-3 grid size-11 place-items-center rounded-xl bg-accent-500 text-lg font-bold text-white">ح</span>
                    @endif
                    <p class="text-base font-semibold text-white">{{ __('common.app_name') }}</p>
                    <p class="mt-2 text-xs text-primary-200">{{ ($branding ?? null)?->tagline() ?: __('common.app_tagline') }}</p>
                    <a href="{{ route('search') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/20 hover:bg-white/20">
                        <x-icon name="search" class="size-3.5"/>
                        {{ __('common.search') }}
                    </a>
                    @if (($branding ?? null)?->social())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($branding->social() as $network => $url)
                                <a href="{{ $url }}" class="text-xs font-medium text-accent-200 hover:text-white" rel="noopener noreferrer" target="_blank">{{ $network }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-accent-300">{{ __('discover.footer.browse') }}</p>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('doctors.index') }}">{{ __('discover.nav.doctors') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('clinics.index') }}">{{ __('discover.nav.clinics') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('specialties.index') }}">{{ __('discover.nav.specialties') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('services.index') }}">{{ __('discover.nav.services') }}</a>
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-accent-300">{{ __('discover.footer.care') }}</p>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('offers.index') }}">{{ __('discover.nav.offers') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('labs.index') }}">{{ __('discover.nav.labs') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('cities.index') }}">{{ __('discover.nav.cities') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('home-care') }}">{{ __('discover.nav.home_care') }}</a>
                    <a class="block text-primary-100 hover:text-white" href="{{ route('teleconsultation') }}">{{ __('discover.nav.teleconsultation') }}</a>
                </div>
                <div class="space-y-2">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-accent-300">{{ __('discover.footer.legal') }}</p>
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
                    <a class="block text-primary-100 hover:text-white" href="{{ route('admin.login') }}">{{ __('auth.admin_login') }}</a>
                </div>
            </div>
            <p class="relative mt-10 text-center text-xs text-primary-300">{{ __('common.app_name') }} — {{ now()->year }}</p>
        </footer>

        <div x-data="siteAgent(@js(route('agent.messages')), @js(csrf_token()), {
                 empty: @js(__('agent.empty')),
             })"
             @open-agent.window="open = true"
             @close-agent.window="open = false">
            <button type="button"
                    @click="toggle()"
                    class="fixed bottom-5 end-5 z-40 hidden size-14 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg ring-4 ring-primary-100 transition hover:bg-primary-700 lg:flex"
                    :aria-label="open ? @js(__('agent.close')) : @js(__('agent.open'))">
                <span x-show="!open"><x-icon name="sparkles" class="size-6"/></span>
                <span x-cloak x-show="open"><x-icon name="x-mark" class="size-6"/></span>
            </button>
            <div x-cloak x-show="open" x-transition
                 class="fixed inset-x-3 bottom-24 z-50 flex h-[min(28rem,70vh)] flex-col overflow-hidden rounded-3xl border border-ink-200 bg-white shadow-xl lg:inset-auto lg:bottom-24 lg:end-5 lg:h-[28rem] lg:w-[22rem]">
                @include('partials.agent-thread')
            </div>
        </div>

        <div x-cloak
             x-show="$store.labCart.toast"
             x-text="$store.labCart.toast"
             class="pointer-events-none fixed bottom-28 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-ink-900 px-4 py-2 text-sm font-medium text-white shadow-lg">
        </div>

        <nav class="fixed inset-x-3 bottom-3 z-50 lg:hidden" aria-label="{{ __('discover.dock.label') }}">
            <div class="flex items-end justify-between rounded-[1.75rem] border border-ink-200/80 bg-white/95 px-1.5 py-1.5 shadow-lg backdrop-blur-md">
                <a href="{{ route('home') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                    <x-icon name="home" class="size-5"/>
                    {{ __('discover.dock.home') }}
                </a>
                @auth
                    <a href="{{ route('appointments.index') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-ink-600">
                        <x-icon name="calendar" class="size-5"/>
                        {{ __('discover.dock.appointments') }}
                    </a>
                @else
                    <a href="{{ route('search') }}" class="flex flex-1 flex-col items-center gap-0.5 rounded-2xl px-1 py-1.5 text-[10px] font-medium text-primary-700">
                        <span class="grid size-8 place-items-center rounded-full bg-primary-50 text-primary-700">
                            <x-icon name="search" class="size-4"/>
                        </span>
                        {{ __('discover.dock.search') }}
                    </a>
                @endauth
                <button type="button"
                        @click="$dispatch('open-agent')"
                        class="-mt-7 flex size-14 flex-col items-center justify-center rounded-full bg-primary-600 text-white shadow-lg ring-4 ring-white"
                        aria-label="{{ __('discover.dock.agent') }}">
                    <x-icon name="sparkles" class="size-6"/>
                </button>
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
