@php
    $starts = $values['loyalty.signup_bonus_starts_at'] ?? null;
    $ends = $values['loyalty.signup_bonus_ends_at'] ?? null;
    try {
        $startsValue = filled($starts) ? \Illuminate\Support\Carbon::parse($starts)->format('Y-m-d\TH:i') : '';
    } catch (\Throwable) {
        $startsValue = '';
    }
    try {
        $endsValue = filled($ends) ? \Illuminate\Support\Carbon::parse($ends)->format('Y-m-d\TH:i') : '';
    } catch (\Throwable) {
        $endsValue = '';
    }
@endphp

<x-layouts.admin :title="__('admin.loyalty.heading')">
    <x-page-header :title="__('admin.loyalty.heading')" :subtitle="__('admin.loyalty.subheading')"/>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-card :title="__('admin.loyalty.your_link')">
            <p class="mb-3 text-sm text-ink-500">{{ __('admin.loyalty.your_link_hint') }}</p>
            <p class="mb-2 text-sm font-semibold tabular text-ink-900">{{ number_format((float) $balance, 2) }} {{ __('common.currency') }}</p>
            @if ($referralUrl)
                <x-copy-field :value="$referralUrl"/>
            @endif
        </x-card>
        <x-card :title="__('admin.loyalty.campaign_status')">
            @if ($campaign)
                @if ($campaign['banner'] ?? true)
                    <p class="text-sm font-semibold text-success-700">{{ __('admin.loyalty.campaign_live') }}</p>
                @else
                    <p class="text-sm font-semibold text-warning-700">{{ __('admin.loyalty.campaign_live_hidden') }}</p>
                @endif
                <p class="mt-1 text-sm text-ink-600">{{ $campaign['headline'] }}</p>
                <p class="mt-1 text-xs text-ink-500">{{ $campaign['body'] }}</p>
            @else
                <p class="text-sm text-ink-500">{{ __('admin.loyalty.campaign_off') }}</p>
            @endif
        </x-card>
    </div>

    <form method="POST" action="{{ route('admin.loyalty.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-card :title="__('admin.loyalty.referral')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-checkbox name="loyalty[referral_enabled]" :label="__('admin.loyalty.referral_enabled')"
                            :checked="(bool) $values['loyalty.referral_enabled']"
                            :hint="__('admin.loyalty.referral_hint')"/>
                <x-field :label="__('admin.loyalty.referral_amount')" name="loyalty.referral_reward_amount" required>
                    <x-input name="loyalty[referral_reward_amount]" type="number" min="0" step="1"
                             :value="$values['loyalty.referral_reward_amount']" dir="ltr"/>
                </x-field>
            </div>
        </x-card>

        <x-card :title="__('admin.loyalty.signup')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-checkbox name="loyalty[signup_bonus_enabled]" :label="__('admin.loyalty.signup_enabled')"
                            :checked="(bool) $values['loyalty.signup_bonus_enabled']"
                            :hint="__('admin.loyalty.signup_hint')"/>
                <x-checkbox name="loyalty[signup_banner_enabled]" :label="__('admin.loyalty.signup_banner_enabled')"
                            :checked="(bool) ($values['loyalty.signup_banner_enabled'] ?? true)"
                            :hint="__('admin.loyalty.signup_banner_hint')"/>
                <x-field :label="__('admin.loyalty.signup_amount')" name="loyalty.signup_bonus_amount" required>
                    <x-input name="loyalty[signup_bonus_amount]" type="number" min="0" step="1"
                             :value="$values['loyalty.signup_bonus_amount']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.loyalty.starts_at')" name="loyalty.signup_bonus_starts_at">
                    <x-input name="loyalty[signup_bonus_starts_at]" type="datetime-local" :value="$startsValue" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.loyalty.ends_at')" name="loyalty.signup_bonus_ends_at">
                    <x-input name="loyalty[signup_bonus_ends_at]" type="datetime-local" :value="$endsValue" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.loyalty.headline_ar')" name="loyalty.signup_headline_ar">
                    <x-input name="loyalty[signup_headline_ar]" :value="$values['loyalty.signup_headline_ar']"/>
                </x-field>
                <x-field :label="__('admin.loyalty.headline_en')" name="loyalty.signup_headline_en">
                    <x-input name="loyalty[signup_headline_en]" :value="$values['loyalty.signup_headline_en']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.loyalty.body_ar')" name="loyalty.signup_body_ar" class="sm:col-span-2">
                    <x-textarea name="loyalty[signup_body_ar]" rows="2" :value="$values['loyalty.signup_body_ar']"/>
                </x-field>
                <x-field :label="__('admin.loyalty.body_en')" name="loyalty.signup_body_en" class="sm:col-span-2">
                    <x-textarea name="loyalty[signup_body_en]" rows="2" :value="$values['loyalty.signup_body_en']" dir="ltr"/>
                </x-field>
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
        </div>
    </form>
</x-layouts.admin>
