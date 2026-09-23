<x-layouts.clinic :title="__('clinic.dashboard.heading')">
    @php
        $user = auth()->user();
        $setupIncomplete = $access->canManage() && count($pending) > 0;
        $funnelMax = max(1, ...array_values($todayStatuses));
    @endphp

    <x-dashboard-hero
        tone="clinic"
        :eyebrow="$greetingDate->translatedFormat('l j F Y')"
        :title="__('clinic.dashboard.welcome', ['name' => $user->name])"
        :subtitle="$clinic->name"
    >
        <x-slot:stats>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $todayStatuses['pending'] ?? 0 }}</p>
                <p class="text-xs text-white/80">{{ __('clinic.dashboard.today_pending') }}</p>
            </div>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $todayStatuses['in_progress'] ?? 0 }}</p>
                <p class="text-xs text-white/80">{{ __('clinic.dashboard.in_clinic') }}</p>
            </div>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                <p class="text-2xl font-semibold tabular">{{ $todayStatuses['completed'] ?? 0 }}</p>
                <p class="text-xs text-white/80">{{ __('clinic.dashboard.completed_today') }}</p>
            </div>
        </x-slot:stats>
        <x-slot:actions>
            <x-button :href="route('clinic.queue.index')" variant="accent" size="sm">
                {{ __('clinic.nav.queue') }}
            </x-button>
            <x-button :href="route('clinic.bookings.index')" variant="secondary" size="sm" class="!border-white/30 !bg-white/10 !text-white hover:!bg-white/20">
                {{ __('clinic.nav.bookings') }}
            </x-button>
            <x-button :href="route('clinic.queue.create')" variant="secondary" size="sm" class="!border-white/30 !bg-white/10 !text-white hover:!bg-white/20">
                {{ __('clinic.queue.add') }}
            </x-button>
        </x-slot:actions>
    </x-dashboard-hero>

    @if ($setupIncomplete)
        <x-alert tone="warning" class="mt-5">{{ __('clinic.dashboard.pending_hint') }}</x-alert>
    @endif

    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <x-metric-tile :label="__('booking.status.pending')" :value="$todayStatuses['pending'] ?? 0" :href="route('clinic.queue.index', ['status' => 'pending'])" tone="warning" icon="clock"/>
        <x-metric-tile :label="__('booking.status.confirmed')" :value="$todayStatuses['confirmed'] ?? 0" :href="route('clinic.queue.index', ['status' => 'confirmed'])" tone="primary" icon="calendar"/>
        <x-metric-tile :label="__('booking.status.in_progress')" :value="$todayStatuses['in_progress'] ?? 0" :href="route('clinic.queue.index', ['status' => 'in_progress'])" tone="accent" icon="stethoscope"/>
        <x-metric-tile :label="__('booking.status.completed')" :value="$todayStatuses['completed'] ?? 0" :href="route('clinic.queue.index', ['status' => 'completed'])" tone="success" icon="check"/>
        <x-metric-tile :label="__('clinic.dashboard.unpaid_today')" :value="$unpaidToday" tone="warning" icon="credit-card"/>
        @if ($showLabs)
            <x-metric-tile :label="__('clinic.dashboard.labs_pending')" :value="$todayLabStatuses['pending'] ?? 0" :href="route('clinic.lab-orders.index', ['status' => 'pending'])" tone="accent" icon="beaker"/>
        @endif
        @if ($access->canManage())
            <x-metric-tile :label="__('clinic.dashboard.doctors')" :value="$clinic->doctors->count()" :href="route('clinic.doctors.index')" tone="ink" icon="users"/>
            <x-metric-tile :label="__('clinic.dashboard.addresses')" :value="$clinic->addresses->count()" :href="route('clinic.addresses.index')" tone="ink" icon="map"/>
        @endif
        @if ($access->canManageBilling())
            <x-metric-tile :label="__('clinic.dashboard.plan')" :value="$clinic->plan?->name ?? '—'" :href="route('clinic.subscription.edit')" tone="primary" icon="credit-card"/>
        @endif
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-5">
        <x-card class="lg:col-span-2" :title="__('clinic.dashboard.today_flow')">
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

        <x-card class="lg:col-span-3" :title="__('clinic.dashboard.next_visits')" padded="false">
            @if ($upcoming->isEmpty())
                <x-empty-state class="p-5" :message="__('clinic.dashboard.empty_today')"/>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($upcoming as $booking)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-ink-900">{{ $booking->patient?->name }}</span>
                                <span class="block truncate text-xs text-ink-400">{{ $booking->doctor?->name }} · {{ $booking->serviceType?->name }}</span>
                            </span>
                            <span class="shrink-0 text-end">
                                <x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge>
                                <span class="mt-1 block text-xs tabular text-ink-500">{{ $booking->scheduled_at?->format('H:i') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        @if ($setupIncomplete)
            <x-card :title="__('clinic.dashboard.setup')" :subtitle="__('clinic.dashboard.setup_hint')">
                <ul class="space-y-2.5">
                    @foreach (['address', 'doctor', 'service', 'schedule'] as $step)
                        @php $done = ! in_array($step, $pending, true); @endphp
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
                                {{ __("clinic.dashboard.steps.$step") }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($access->canManage())
            <x-card :title="__('clinic.dashboard.verification')">
                <p class="text-sm text-ink-600">
                    {{ $clinic->email }} · {{ $clinic->phone }}
                </p>
                <div class="mt-3">
                    <x-badge :tone="$clinic->verification_status->tone()">{{ $clinic->verification_status->label() }}</x-badge>
                </div>
                @if ($clinic->currentSubscription)
                    <p class="mt-3 text-sm text-ink-500">
                        {{ $clinic->currentSubscription->plan?->name }}
                        · {{ $clinic->currentSubscription->billing_cycle->label() }}
                        · <x-badge :tone="$clinic->currentSubscription->status->tone()">{{ $clinic->currentSubscription->status->label() }}</x-badge>
                    </p>
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.clinic>
