@props(['title' => null])

@php
    $user = auth()->user();
    $clinic = $currentClinic;
    $access = $clinicAccess;
@endphp

<x-layouts.base :title="$title ? $title.' — '.__('clinic.title') : __('clinic.title')" body-class="min-h-screen theme-v2" theme-color="#3B82F6">
    <div x-data="{ sidebar: false }" class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 z-40 w-64 shrink-0 overflow-y-auto border-e border-ink-200 bg-white p-4 transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : (document.documentElement.dir === 'rtl' ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0')"
        >
            <a href="{{ route('clinic.dashboard') }}" class="flex items-center gap-2.5 px-2 py-1">
                <span class="grid size-9 place-items-center overflow-hidden rounded-xl bg-primary-600 text-lg font-bold text-white">
                    @if ($clinic->logo_path)
                        <img src="{{ \App\Support\PublicImage::url($clinic->logo_path) }}" alt="" class="size-9 object-cover">
                    @else
                        ح
                    @endif
                </span>
                <span>
                    <span class="block truncate text-sm font-bold text-ink-900">{{ $clinic->name }}</span>
                    <span class="block text-[11px] text-ink-500">{{ __('clinic.title') }}</span>
                </span>
            </a>

            <nav class="mt-4 space-y-0.5">
                <x-admin.nav-item :href="route('clinic.dashboard')" icon="grid" pattern="clinic.dashboard">
                    {{ __('clinic.nav.overview') }}
                </x-admin.nav-item>
                <x-admin.nav-item :href="route('account.edit')" icon="user" pattern="account.*">
                    {{ __('clinic.nav.account') }}
                </x-admin.nav-item>

                @if ($access->canManage())
                    <x-admin.nav-item :href="route('clinic.profile.edit')" icon="building" pattern="clinic.profile.*">
                        {{ __('clinic.nav.profile') }}
                    </x-admin.nav-item>
                @endif

                <x-admin.nav-item :href="route('clinic.addresses.index')" icon="map" pattern="clinic.addresses.*">
                    {{ __('clinic.nav.addresses') }}
                </x-admin.nav-item>

                <x-admin.nav-item :href="route('clinic.doctors.index')" icon="stethoscope" pattern="clinic.doctors.*">
                    {{ __('clinic.nav.doctors') }}
                </x-admin.nav-item>

                @if ($access->canManage())
                    <x-admin.nav-item :href="route('clinic.services.edit')" icon="layers" pattern="clinic.services.*">
                        {{ __('clinic.nav.services') }}
                    </x-admin.nav-item>
                    @if ($clinic->hasModule(\App\Enums\ClinicModule::Labs))
                        <x-admin.nav-item :href="route('clinic.labs.edit')" icon="beaker" pattern="clinic.labs.*">
                            {{ __('clinic.nav.labs') }}
                        </x-admin.nav-item>
                    @endif
                    @if ($clinic->hasModule(\App\Enums\ClinicModule::Promotions))
                        <x-admin.nav-item :href="route('clinic.offers.index')" icon="megaphone" pattern="clinic.offers.*">
                            {{ __('clinic.nav.offers') }}
                        </x-admin.nav-item>
                    @endif
                @endif

                @if ($access->canManageBilling())
                    <x-admin.nav-item :href="route('clinic.subscription.edit')" icon="credit-card" pattern="clinic.subscription.*">
                        {{ __('clinic.nav.subscription') }}
                    </x-admin.nav-item>
                @endif

                @if ($access->canManage())
                    <x-admin.nav-item :href="route('clinic.payments.edit')" icon="credit-card" pattern="clinic.payments.*">
                        {{ __('clinic.nav.payments') }}
                    </x-admin.nav-item>
                @endif

                @if ($access->canManageStaff())
                    <x-admin.nav-item :href="route('clinic.staff.index')" icon="users" pattern="clinic.staff.*">
                        {{ __('clinic.nav.staff') }}
                    </x-admin.nav-item>
                @endif

                <x-admin.nav-item :href="route('clinic.queue.index')" icon="clock" pattern="clinic.queue.*">
                    {{ __('clinic.nav.queue') }}
                </x-admin.nav-item>
                <x-admin.nav-item :href="route('clinic.bookings.index')" icon="calendar" pattern="clinic.bookings.*">
                    {{ __('clinic.nav.bookings') }}
                </x-admin.nav-item>
                @if ($clinic->hasModule(\App\Enums\ClinicModule::Labs))
                    <x-admin.nav-item :href="route('clinic.lab-orders.index')" icon="beaker" pattern="clinic.lab-orders.*">
                        {{ __('clinic.nav.lab_orders') }}
                    </x-admin.nav-item>
                @endif
                <x-admin.nav-item :href="route('clinic.attendance.index')" icon="clock" pattern="clinic.attendance.*">
                    {{ __('clinic.nav.attendance') }}
                </x-admin.nav-item>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-ink-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                <button type="button" class="rounded-lg p-2 text-ink-500 hover:bg-ink-100 lg:hidden" @click="sidebar = !sidebar">
                    <x-icon name="grid"/>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-ink-400">{{ __('clinic.signed_in_as') }}</p>
                    <p class="truncate text-sm font-medium text-ink-800">
                        {{ $user->name }}
                        <span class="text-ink-400">·</span>
                        <span class="text-xs text-ink-500">{{ $clinic->plan?->name }}</span>
                    </p>
                </div>

                <x-badge :tone="$clinic->verification_status->tone()">
                    {{ $clinic->verification_status->label() }}
                </x-badge>

                <x-inbox-bell/>

                <a href="{{ route('account.edit') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-600 hover:bg-ink-100">
                    <x-icon name="user" class="size-4"/>
                    <span class="hidden sm:inline">{{ __('account.profile') }}</span>
                </a>

                <x-locale-switcher/>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-600 hover:bg-ink-100">
                        <x-icon name="logout" class="size-4"/>
                        <span class="hidden sm:inline">{{ __('common.logout') }}</span>
                    </button>
                </form>
            </header>

            <main class="relative flex-1 px-4 py-6 lg:px-8">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-primary-100/80 to-transparent"></div>
                <div class="relative">
                @if (session('status'))
                    <x-alert tone="success" class="mb-5">{{ session('status') }}</x-alert>
                @endif

                @if (session('error'))
                    <x-alert tone="danger" class="mb-5">{{ session('error') }}</x-alert>
                @endif

                @if (session('profile_complete'))
                    <x-alert tone="success" class="mb-5">{{ __('account.complete') }}</x-alert>
                @elseif (! ($branding ?? app(\App\Support\Branding::class))->profileComplete($user))
                    @php
                        $firstGap = ($branding ?? app(\App\Support\Branding::class))->profileGaps($user)[0]['label'] ?? null;
                    @endphp
                    <x-alert tone="warning" class="mb-5">
                        <a href="{{ route('account.edit') }}" class="font-medium underline-offset-2 hover:underline">
                            {{ __('account.incomplete') }}
                            @if ($firstGap)
                                <span class="font-semibold">— {{ $firstGap }}</span>
                            @endif
                        </a>
                    </x-alert>
                @endif

                @if ($errors->any())
                    <x-alert tone="danger" class="mb-5">{{ $errors->first() }}</x-alert>
                @endif

                {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
