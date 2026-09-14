<x-layouts.clinic :title="__('clinic.dashboard.heading')">
    <x-page-header :title="$clinic->name" :subtitle="__('clinic.dashboard.subheading')">
        <x-slot:actions>
            <x-badge :tone="$clinic->verification_status->tone()">{{ $clinic->verification_status->label() }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <x-alert tone="warning" class="mb-5">{{ __('clinic.dashboard.pending_hint') }}</x-alert>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('clinic.dashboard.doctors')" :value="$clinic->doctors->count()" :href="route('clinic.doctors.index')"/>
        <x-stat :label="__('clinic.dashboard.addresses')" :value="$clinic->addresses->count()" :href="route('clinic.addresses.index')"/>
        <x-stat :label="__('clinic.dashboard.services')" :value="$clinic->services->where('is_active', true)->count()" :href="route('clinic.services.edit')"/>
        <x-stat :label="__('clinic.dashboard.plan')" :value="$clinic->plan?->name ?? '—'" tone="accent" :href="$access->canManageBilling() ? route('clinic.subscription.edit') : null"/>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
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

        <x-card :title="__('clinic.dashboard.verification')">
            <p class="text-sm text-ink-600">
                {{ $clinic->email }} · {{ $clinic->phone }}
            </p>
            @if ($clinic->currentSubscription)
                <p class="mt-3 text-sm text-ink-500">
                    {{ $clinic->currentSubscription->plan?->name }}
                    · {{ $clinic->currentSubscription->billing_cycle->label() }}
                    · <x-badge :tone="$clinic->currentSubscription->status->tone()">{{ $clinic->currentSubscription->status->label() }}</x-badge>
                </p>
            @endif

            @if ($access->isReception())
                <p class="mt-4 text-sm text-ink-500">{{ __('clinic.queue_soon') }}</p>
            @endif
        </x-card>
    </div>
</x-layouts.clinic>
