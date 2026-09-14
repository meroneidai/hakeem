<x-layouts.clinic :title="__('clinic.subscription.heading')">
    <x-page-header :title="__('clinic.subscription.heading')" :subtitle="__('clinic.subscription.subtitle')"/>

    <x-alert tone="info" class="mb-5">{{ __('clinic.subscription.payment_stub') }}</x-alert>

    @if ($clinic->currentSubscription)
        <x-card class="mb-5">
            <dl class="grid gap-3 sm:grid-cols-4 text-sm">
                <div>
                    <dt class="text-ink-400">{{ __('clinic.subscription.current') }}</dt>
                    <dd class="mt-1 font-medium text-ink-900">{{ $clinic->currentSubscription->plan?->name }}</dd>
                </div>
                <div>
                    <dt class="text-ink-400">{{ __('clinic.subscription.cycle') }}</dt>
                    <dd class="mt-1 font-medium text-ink-900">{{ $clinic->currentSubscription->billing_cycle->label() }}</dd>
                </div>
                <div>
                    <dt class="text-ink-400">{{ __('clinic.subscription.amount') }}</dt>
                    <dd class="mt-1 font-medium text-ink-900">{{ number_format((float) $clinic->currentSubscription->amount, 2) }} {{ __('common.currency') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-400">{{ __('common.status') }}</dt>
                    <dd class="mt-1">
                        <x-badge :tone="$clinic->currentSubscription->status->tone()">
                            {{ $clinic->currentSubscription->status->label() }}
                        </x-badge>
                    </dd>
                </div>
            </dl>
        </x-card>
    @endif

    <form method="POST" action="{{ route('clinic.subscription.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid gap-3 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <label class="card cursor-pointer p-4">
                    <input type="radio" name="subscription_plan_id" value="{{ $plan->id }}" class="sr-only peer"
                           @checked((int) old('subscription_plan_id', $clinic->subscription_plan_id) === $plan->id)>
                    <div class="rounded-xl p-1 peer-checked:ring-2 peer-checked:ring-primary-400 -m-4 p-4">
                        <span class="block font-semibold text-ink-900">{{ $plan->name }}</span>
                        <span class="mt-1 block text-sm text-primary-700">
                            @if ($plan->is_default_free || (float) $plan->monthly_price === 0.0)
                                {{ __('common.unlimited') }}
                            @else
                                {{ number_format((float) $plan->monthly_price) }} {{ __('common.currency') }}{{ __('common.per_month') }}
                            @endif
                        </span>
                        <p class="mt-2 text-xs text-ink-500">{{ $plan->description }}</p>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="grid max-w-xl gap-4 sm:grid-cols-2">
            <x-field :label="__('clinic.subscription.cycle')" name="billing_cycle" required>
                <x-select name="billing_cycle" :selected="old('billing_cycle', $clinic->currentSubscription?->billing_cycle->value ?? 'monthly')"
                          :options="[
                              'monthly' => __('clinic.register.billing_monthly'),
                              'yearly' => __('clinic.register.billing_yearly'),
                          ]"/>
            </x-field>
            <x-field :label="__('clinic.register.discount')" name="discount_code">
                <x-input name="discount_code" dir="ltr"/>
            </x-field>
        </div>

        <x-button variant="accent">{{ __('clinic.subscription.choose') }}</x-button>
    </form>
</x-layouts.clinic>
