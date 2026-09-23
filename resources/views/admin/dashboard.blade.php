<x-layouts.admin :title="__('admin.dashboard.heading')">
    @php
        $user = auth()->user();
        $checklistDone = ! in_array(false, $checklist, true);
        $funnelMax = max(1, ...array_values($todayStatuses));
    @endphp

    <x-dashboard-hero
        :eyebrow="$greetingDate->translatedFormat('l j F Y')"
        :title="__('admin.dashboard.welcome', ['name' => $user->name])"
        :subtitle="__('admin.dashboard.live_subheading')"
    >
        <x-slot:stats>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $counts['bookings_today'] }}</p>
                <p class="text-xs text-white/80">{{ __('admin.dashboard.bookings_today') }}</p>
            </div>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $todayStatuses['pending'] ?? 0 }}</p>
                <p class="text-xs text-white/80">{{ __('admin.dashboard.pending_today') }}</p>
            </div>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $counts['open_tickets'] }}</p>
                <p class="text-xs text-white/80">{{ __('admin.dashboard.open_tickets') }}</p>
            </div>
        </x-slot:stats>
        <x-slot:actions>
            @can(\App\Enums\Permission::OverseeBookings->value)
                <x-button :href="route('admin.bookings.index')" variant="accent" size="sm">
                    {{ __('admin.nav.bookings') }}
                </x-button>
            @endcan
            <x-button :href="route('admin.support.index')" variant="secondary" size="sm" class="!border-white/30 !bg-white/10 !text-white hover:!bg-white/20">
                {{ __('admin.nav.support') }}
            </x-button>
        </x-slot:actions>
    </x-dashboard-hero>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @can(\App\Enums\Permission::OverseeBookings->value)
            <x-metric-tile :label="__('admin.dashboard.bookings_today')" :value="$counts['bookings_today']" :href="route('admin.bookings.index')" tone="primary" icon="calendar"/>
            <x-metric-tile :label="__('booking.status.pending')" :value="$todayStatuses['pending'] ?? 0" :href="route('admin.bookings.index', ['status' => 'pending', 'from' => now()->toDateString(), 'to' => now()->toDateString()])" tone="warning" icon="clock"/>
            <x-metric-tile :label="__('booking.status.completed')" :value="$todayStatuses['completed'] ?? 0" :href="route('admin.bookings.index', ['status' => 'completed', 'from' => now()->toDateString(), 'to' => now()->toDateString()])" tone="success" icon="check"/>
            <x-metric-tile :label="__('admin.dashboard.labs_today')" :value="$counts['labs_today']" :href="route('admin.lab-orders.index')" tone="accent" icon="beaker"/>
            <x-metric-tile :label="__('admin.dashboard.unpaid_today')" :value="$counts['unpaid_today']" tone="warning" icon="credit-card"/>
        @endcan
        @can(\App\Enums\Permission::ManageUsers->value)
            <x-metric-tile :label="__('admin.dashboard.patients')" :value="$counts['patients']" :href="route('admin.users.index')" tone="primary" icon="users"/>
        @endcan
        @can(\App\Enums\Permission::ModerateClinics->value)
            <x-metric-tile :label="__('admin.dashboard.clinics')" :value="$counts['clinics']" :href="route('admin.clinics.index')" tone="accent" icon="building"/>
        @endcan
        @can(\App\Enums\Permission::ViewErrorReports->value)
            <x-metric-tile :label="__('admin.nav.errors')" :value="$counts['open_errors']" :href="route('admin.errors.index')" tone="danger" icon="shield"/>
        @endcan
        @can(\App\Enums\Permission::ManagePromotions->value)
            <x-metric-tile :label="__('admin.dashboard.promotions_running')" :value="$counts['promotions_running']" :href="route('admin.promotions.index')" tone="accent" icon="megaphone"/>
        @endcan
        @can(\App\Enums\Permission::ManageGeography->value)
            <x-metric-tile :label="__('admin.dashboard.governorates')" :value="$counts['governorates']" :href="route('admin.governorates.index')" tone="ink" icon="map"/>
            <x-metric-tile :label="__('admin.dashboard.specialties')" :value="$counts['specialties']" :href="route('admin.specialties.index')" tone="ink" icon="stethoscope"/>
        @endcan
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-5">
        @can(\App\Enums\Permission::OverseeBookings->value)
            <x-card class="lg:col-span-2" :title="__('admin.dashboard.today_flow')" :subtitle="__('admin.dashboard.today_flow_hint')">
                <ul class="space-y-3">
                    @foreach (\App\Enums\BookingStatus::cases() as $status)
                        @php $count = $todayStatuses[$status->value] ?? 0; @endphp
                        <li>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-ink-700">{{ $status->label() }}</span>
                                <span class="tabular text-ink-500">{{ $count }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-warning-500' => $status->tone() === 'warning',
                                    'bg-primary-500' => $status->tone() === 'primary',
                                    'bg-accent-500' => $status->tone() === 'accent',
                                    'bg-success-500' => $status->tone() === 'success',
                                    'bg-danger-500' => $status->tone() === 'danger',
                                ]) style="width: {{ round(($count / $funnelMax) * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card class="lg:col-span-3" :title="__('admin.dashboard.recent_bookings')" padded="false">
                @if ($recentBookings->isEmpty())
                    <x-empty-state class="p-5"/>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($recentBookings as $booking)
                            <li>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-ink-50">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-ink-900">{{ $booking->patient?->name }}</span>
                                        <span class="block truncate text-xs text-ink-400">{{ $booking->clinic?->name }} · {{ $booking->doctor?->name }}</span>
                                    </span>
                                    <span class="shrink-0 text-end">
                                        <x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge>
                                        <span class="mt-1 block text-xs tabular text-ink-400">{{ $booking->scheduled_at?->format('Y-m-d H:i') }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        @endcan
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        @can(\App\Enums\Permission::ManageGeography->value)
            @unless ($checklistDone)
                <x-card :title="__('admin.dashboard.setup_checklist')" :subtitle="__('admin.dashboard.setup_hint')">
                    <ul class="space-y-2.5">
                        @foreach ($checklist as $key => $done)
                            <li class="flex items-center gap-2.5 text-sm">
                                <span @class([
                                    'grid size-5 shrink-0 place-items-center rounded-full',
                                    'bg-success-500 text-white' => $done,
                                    'border border-ink-300 text-ink-300' => ! $done,
                                ])>
                                    @if ($done)
                                        <x-icon name="check" class="size-3"/>
                                    @endif
                                </span>
                                <span class="{{ $done ? 'text-ink-500 line-through' : 'font-medium text-ink-800' }}">
                                    {{ __("admin.checklist.$key") }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endunless
        @endcan

        <x-card :title="__('admin.dashboard.recent_activity')">
            @if ($recentActivity->isEmpty())
                <x-empty-state/>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($recentActivity as $log)
                        <li class="flex items-baseline justify-between gap-3 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="font-medium text-ink-800">{{ $log->user?->name ?? __('admin.audit.system') }}</span>
                                <span class="text-ink-400">·</span>
                                <code class="text-xs text-primary-600">{{ $log->action }}</code>
                            </span>
                            <span class="shrink-0 text-xs text-ink-400">{{ $log->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    @can(\App\Enums\Permission::ViewAnalytics->value)
        <x-card class="mt-5" :title="__('admin.dashboard.marketplace')" :subtitle="__('admin.dashboard.marketplace_hint')">
            <div class="grid gap-3 sm:grid-cols-4">
                <x-metric-tile :label="__('admin.analytics.bookings_30')" :value="$insightsSnapshot['bookings']" tone="primary" icon="calendar"/>
                <x-metric-tile :label="__('admin.analytics.completed_30')" :value="$insightsSnapshot['completed']" tone="success" icon="check"/>
                <x-metric-tile :label="__('admin.dashboard.active_clinics')" :value="$insightsSnapshot['active_clinics']" tone="accent" icon="building"/>
                <x-metric-tile :label="__('admin.users.app')" :value="$insightsSnapshot['app_installed']" tone="ink" icon="device-phone"/>
            </div>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div>
                    <p class="mb-2 text-sm font-semibold text-ink-800">{{ __('admin.analytics.top_clinics') }}</p>
                    <ul class="space-y-1 text-sm">
                        @forelse ($insightsSnapshot['top_clinics'] as $clinic)
                            <li class="flex justify-between"><span>{{ $clinic->name }}</span><span class="tabular text-ink-400">{{ $clinic->period_bookings_count }}</span></li>
                        @empty
                            <li class="text-ink-400">{{ __('common.no_results') }}</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <p class="mb-2 text-sm font-semibold text-ink-800">{{ __('admin.analytics.top_doctors') }}</p>
                    <ul class="space-y-1 text-sm">
                        @forelse ($insightsSnapshot['top_doctors'] as $doctor)
                            <li class="flex justify-between"><span>{{ $doctor->name }}</span><span class="tabular text-ink-400">{{ $doctor->period_bookings_count }}</span></li>
                        @empty
                            <li class="text-ink-400">{{ __('common.no_results') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </x-card>
    @endcan
</x-layouts.admin>
