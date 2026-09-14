@props(['title' => null])

@php
    $user = auth()->user();
    $clinic = $currentClinic;
    $access = $clinicAccess;
@endphp

<x-layouts.base :title="$title ? $title.' — '.__('clinic.title') : __('clinic.title')">
    <div x-data="{ sidebar: false }" class="flex min-h-screen">
        <aside
            class="fixed inset-y-0 z-40 w-64 shrink-0 overflow-y-auto border-e border-ink-200 bg-white p-4 transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : (document.documentElement.dir === 'rtl' ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0')"
        >
            <a href="{{ route('clinic.dashboard') }}" class="flex items-center gap-2.5 px-2 py-1">
                <span class="grid size-9 place-items-center overflow-hidden rounded-xl bg-primary-600 text-lg font-bold text-white">
                    @if ($clinic->logo_path)
                        <img src="{{ Storage::url($clinic->logo_path) }}" alt="" class="size-9 object-cover">
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
                @endif

                @if ($access->canManageBilling())
                    <x-admin.nav-item :href="route('clinic.subscription.edit')" icon="credit-card" pattern="clinic.subscription.*">
                        {{ __('clinic.nav.subscription') }}
                    </x-admin.nav-item>
                @endif

                @if ($access->canManageStaff())
                    <x-admin.nav-item :href="route('clinic.staff.index')" icon="users" pattern="clinic.staff.*">
                        {{ __('clinic.nav.staff') }}
                    </x-admin.nav-item>
                @endif

                <div class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-ink-400">
                    {{ __('clinic.nav.queue') }}
                </div>
                <p class="px-3 text-xs leading-5 text-ink-400">{{ __('clinic.queue_soon') }}</p>
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

                <x-locale-switcher/>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-600 hover:bg-ink-100">
                        <x-icon name="logout" class="size-4"/>
                        <span class="hidden sm:inline">{{ __('common.logout') }}</span>
                    </button>
                </form>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <x-alert tone="success" class="mb-5">{{ session('status') }}</x-alert>
                @endif

                @if (session('error'))
                    <x-alert tone="danger" class="mb-5">{{ session('error') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
