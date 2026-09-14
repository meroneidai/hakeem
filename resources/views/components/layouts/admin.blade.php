@props(['title' => null])

@php
    use App\Enums\Permission;

    $user = auth()->user();
    $openTickets = \App\Models\SupportTicket::unresolved()->count();
@endphp

<x-layouts.base :title="$title ? $title.' — '.__('admin.title') : __('admin.title')">
    <div x-data="{ sidebar: false }" class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 z-40 w-64 shrink-0 overflow-y-auto border-e border-ink-200 bg-white p-4 transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : (document.documentElement.dir === 'rtl' ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0')"
        >
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-2 py-1">
                <span class="grid size-9 place-items-center rounded-xl bg-primary-600 text-lg font-bold text-white">ح</span>
                <span>
                    <span class="block text-sm font-bold text-ink-900">{{ __('common.app_name') }}</span>
                    <span class="block text-[11px] text-ink-500">{{ __('admin.title') }}</span>
                </span>
            </a>

            <nav class="mt-4 space-y-0.5">
                <x-admin.nav-item :href="route('admin.dashboard')" icon="grid" pattern="admin.dashboard">
                    {{ __('admin.nav.overview') }}
                </x-admin.nav-item>

                <x-admin.nav-group :label="__('admin.nav.reference_data')">
                    <x-admin.nav-item :href="route('admin.governorates.index')" icon="map" pattern="admin.governorates.*">
                        {{ __('admin.nav.governorates') }}
                    </x-admin.nav-item>
                    <x-admin.nav-item :href="route('admin.cities.index')" icon="building" pattern="admin.cities.*">
                        {{ __('admin.nav.cities') }}
                    </x-admin.nav-item>
                    <x-admin.nav-item :href="route('admin.specialties.index')" icon="stethoscope" pattern="admin.specialties.*">
                        {{ __('admin.nav.specialties') }}
                    </x-admin.nav-item>
                    <x-admin.nav-item :href="route('admin.service-types.index')" icon="layers" pattern="admin.service-types.*">
                        {{ __('admin.nav.service_types') }}
                    </x-admin.nav-item>
                </x-admin.nav-group>

                @can(Permission::ManageSubscriptionPlans->value)
                    <x-admin.nav-group :label="__('admin.nav.monetization')">
                        <x-admin.nav-item :href="route('admin.plans.index')" icon="layers" pattern="admin.plans.*">
                            {{ __('admin.nav.plans') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.discount-codes.index')" icon="ticket" pattern="admin.discount-codes.*">
                            {{ __('admin.nav.discount_codes') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.payments.edit')" icon="credit-card" pattern="admin.payments.*">
                            {{ __('admin.nav.payments') }}
                        </x-admin.nav-item>
                    </x-admin.nav-group>
                @endcan

                @can(Permission::ManagePromotions->value)
                    <x-admin.nav-group :label="__('admin.nav.growth')">
                        <x-admin.nav-item :href="route('admin.promotions.index')" icon="megaphone" pattern="admin.promotions.*">
                            {{ __('admin.nav.promotions') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.seo-pages.index')" icon="search" pattern="admin.seo-pages.*">
                            {{ __('admin.nav.seo') }}
                        </x-admin.nav-item>
                    </x-admin.nav-group>
                @endcan

                <x-admin.nav-group :label="__('admin.nav.operations')">
                    <x-admin.nav-item :href="route('admin.support.index')" icon="ticket" pattern="admin.support.*"
                                      :badge="$openTickets ?: null">
                        {{ __('admin.nav.support') }}
                    </x-admin.nav-item>

                    @can(Permission::ManageStaff->value)
                        <x-admin.nav-item :href="route('admin.staff.index')" icon="users" pattern="admin.staff.*">
                            {{ __('admin.nav.staff') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ManagePaymentSettings->value)
                        <x-admin.nav-item :href="route('admin.notifications.edit')" icon="bell" pattern="admin.notifications.*">
                            {{ __('admin.nav.notifications') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ViewAuditLog->value)
                        <x-admin.nav-item :href="route('admin.audit-logs.index')" icon="shield" pattern="admin.audit-logs.*">
                            {{ __('admin.nav.audit_log') }}
                        </x-admin.nav-item>
                    @endcan
                </x-admin.nav-group>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-ink-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                <button type="button" class="rounded-lg p-2 text-ink-500 hover:bg-ink-100 lg:hidden" @click="sidebar = !sidebar">
                    <x-icon name="grid"/>
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-ink-400">{{ __('admin.signed_in_as') }}</p>
                    <p class="truncate text-sm font-medium text-ink-800">
                        {{ $user->name }}
                        <span class="text-ink-400">·</span>
                        <span class="text-xs text-ink-500">
                            {{ $user->roles->map(fn ($role) => $role->label)->join('، ') }}
                        </span>
                    </p>
                </div>

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
