<x-layouts.admin :title="__('admin.plans.heading')">
    <x-page-header :title="__('admin.plans.heading')" :subtitle="__('admin.plans.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.plans.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.plans.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($plans as $plan)
            <x-card @class(['flex flex-col', 'border-accent-300 ring-1 ring-accent-200' => $plan->is_default_free])>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-ink-900">{{ $plan->name_ar }}</h3>
                        <p class="text-xs text-ink-500" dir="ltr">{{ $plan->name_en }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <x-status-dot :active="$plan->is_active"/>
                        @if ($plan->is_default_free)
                            <x-badge tone="accent">{{ __('admin.plans.is_default_free') }}</x-badge>
                        @endif
                    </div>
                </div>

                <p class="mt-4">
                    <span class="text-2xl font-bold text-primary-700 tabular">
                        {{ $plan->monthly_price > 0 ? number_format((float) $plan->monthly_price) : '0' }}
                    </span>
                    <span class="text-sm text-ink-500">{{ __('common.currency') }}{{ __('common.per_month') }}</span>
                </p>

                @if ($plan->monthly_price > 0)
                    <p class="mt-1 text-xs text-ink-500">
                        {{ __('admin.plans.effective_yearly') }}:
                        <span class="tabular font-medium">{{ number_format($plan->effectiveYearlyPrice()) }}</span>
                        {{ __('common.currency') }}
                        @if ($plan->yearly_discount_pct)
                            <x-badge tone="success" class="ms-1">-{{ $plan->yearly_discount_pct }}%</x-badge>
                        @endif
                    </p>
                @endif

                <dl class="mt-4 space-y-1.5 text-sm">
                    @foreach ([
                        'booking_cap' => $plan->booking_cap,
                        'doctor_cap' => $plan->doctor_cap,
                        'address_cap' => $plan->address_cap,
                    ] as $key => $cap)
                        <div class="flex justify-between gap-2">
                            <dt class="text-ink-500">{{ __("admin.plans.$key") }}</dt>
                            <dd class="tabular font-medium text-ink-800">
                                {{ $cap === null ? __('common.unlimited') : number_format($cap) }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-4 text-xs text-ink-500">
                    {{ $plan->featureFlags->where('is_enabled', true)->count() }} / {{ $plan->featureFlags->count() }}
                    {{ __('admin.plans.features') }}
                </p>

                <x-slot:footer>
                    <x-row-actions :edit="route('admin.plans.edit', $plan)"
                                   :destroy="$plan->is_default_free ? null : route('admin.plans.destroy', $plan)"/>
                </x-slot:footer>
            </x-card>
        @endforeach
    </div>
</x-layouts.admin>
