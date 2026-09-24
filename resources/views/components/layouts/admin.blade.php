@props(['title' => null])

@php
    use App\Enums\Permission;

    $user = auth()->user();
    $openTickets = \App\Models\SupportTicket::unresolved()->count();
@endphp

<x-layouts.base :title="$title ? $title.' — '.__('admin.title') : __('admin.title')" body-class="min-h-screen theme-v2" theme-color="#3B82F6">
    <div x-data="{ sidebar: false }" class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 z-40 w-64 shrink-0 overflow-y-auto border-e border-ink-200 bg-white p-4 transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : (document.documentElement.dir === 'rtl' ? 'translate-x-full lg:translate-x-0' : '-translate-x-full lg:translate-x-0')"
        >
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-2 py-1">
                <img
                    src="{{ $branding->logoUrl() }}"
                    alt=""
                    @class([
                        'shrink-0 object-contain',
                        'h-8 w-auto max-w-[9rem]' => ! ($branding?->usesCustomLogo() ?? false),
                        'size-9 rounded-xl object-cover' => $branding?->usesCustomLogo(),
                    ])
                >
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
                    @can(Permission::ManageInsuranceProviders->value)
                        <x-admin.nav-item :href="route('admin.insurance-providers.index')" icon="shield" pattern="admin.insurance-providers.*">
                            {{ __('admin.nav.insurance_providers') }}
                        </x-admin.nav-item>
                    @endcan
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

                @can(Permission::ManageLabCatalog->value)
                    <x-admin.nav-group :label="__('admin.nav.labs')">
                        <x-admin.nav-item :href="route('admin.lab-tests.index')" icon="layers" pattern="admin.lab-tests.*">
                            {{ __('admin.nav.lab_tests') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.lab-packages.index')" icon="ticket" pattern="admin.lab-packages.*">
                            {{ __('admin.nav.lab_packages') }}
                        </x-admin.nav-item>
                    </x-admin.nav-group>
                @endcan

                @can(Permission::ManagePromotions->value)
                    <x-admin.nav-group :label="__('admin.nav.growth')">
                        <x-admin.nav-item :href="route('admin.promotions.index')" icon="megaphone" pattern="admin.promotions.*">
                            {{ __('admin.nav.promotions') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.seo-pages.index')" icon="search" pattern="admin.seo*">
                            {{ __('admin.nav.seo') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.articles.index')" icon="layers" pattern="admin.articles.*">
                            {{ __('admin.nav.articles') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.site-pages.index')" icon="layers" pattern="admin.site-pages.*">
                            {{ __('admin.nav.site_pages') }}
                        </x-admin.nav-item>
                    </x-admin.nav-group>
                @endcan

                <x-admin.nav-group :label="__('admin.nav.people')">
                    @can(Permission::ManageUsers->value)
                        <x-admin.nav-item :href="route('admin.users.index')" icon="users" pattern="admin.users.*">
                            {{ __('admin.nav.users') }}
                        </x-admin.nav-item>
                    @endcan
                    @can(Permission::ModerateClinics->value)
                        <x-admin.nav-item :href="route('admin.clinics.index')" icon="building" pattern="admin.clinics.*">
                            {{ __('admin.nav.clinics') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.doctors.index')" icon="stethoscope" pattern="admin.doctors.*">
                            {{ __('admin.nav.doctors') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.map')" icon="map" pattern="admin.map">
                            {{ __('admin.nav.map') }}
                        </x-admin.nav-item>
                    @endcan
                    @can(Permission::ManageSubscriptionPlans->value)
                        <x-admin.nav-item :href="route('admin.billing')" icon="credit-card" pattern="admin.billing*">
                            {{ __('admin.nav.billing') }}
                        </x-admin.nav-item>
                    @endcan
                    <x-admin.nav-item :href="route('admin.attendance.index')" icon="clock" pattern="admin.attendance.*">
                        {{ __('admin.nav.attendance') }}
                    </x-admin.nav-item>
                </x-admin.nav-group>

                <x-admin.nav-group :label="__('admin.nav.operations')">
                    @can(Permission::OverseeBookings->value)
                        <x-admin.nav-item :href="route('admin.bookings.index')" icon="calendar" pattern="admin.bookings.*">
                            {{ __('admin.nav.bookings') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.lab-orders.index')" icon="beaker" pattern="admin.lab-orders.*">
                            {{ __('admin.nav.lab_orders') }}
                        </x-admin.nav-item>
                    @endcan

                    <x-admin.nav-item :href="route('admin.support.index')" icon="ticket" pattern="admin.support.*"
                                      :badge="$openTickets ?: null">
                        {{ __('admin.nav.support') }}
                    </x-admin.nav-item>
                    @can(Permission::ManageSupportTickets->value)
                        <x-admin.nav-item :href="route('admin.agent-conversations.index')" icon="chat" pattern="admin.agent-conversations.*">
                            {{ __('admin.nav.agent_conversations') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ManageStaff->value)
                        <x-admin.nav-item :href="route('admin.staff.index')" icon="users" pattern="admin.staff.*">
                            {{ __('admin.nav.staff') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ManagePaymentSettings->value)
                        <x-admin.nav-item :href="route('admin.notifications.edit')" icon="bell" pattern="admin.notifications.*">
                            {{ __('admin.nav.notifications') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.system.edit')" icon="key" pattern="admin.system.*">
                            {{ __('admin.nav.system') }}
                        </x-admin.nav-item>
                        <x-admin.nav-item :href="route('admin.loyalty.edit')" icon="sparkles" pattern="admin.loyalty.*">
                            {{ __('admin.nav.loyalty') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ViewAnalytics->value)
                        <x-admin.nav-item :href="route('admin.analytics')" icon="chart" pattern="admin.analytics*">
                            {{ __('admin.nav.analytics') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ModerateReviews->value)
                        <x-admin.nav-item :href="route('admin.reviews.index')" icon="star" pattern="admin.reviews.*">
                            {{ __('admin.nav.reviews') }}
                        </x-admin.nav-item>
                    @endcan

                    @can(Permission::ViewErrorReports->value)
                        <x-admin.nav-item :href="route('admin.errors.index')" icon="shield" pattern="admin.errors.*">
                            {{ __('admin.nav.errors') }}
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

                @if ($errors->any())
                    <x-alert tone="danger" class="mb-5">{{ $errors->first() }}</x-alert>
                @endif

                {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
