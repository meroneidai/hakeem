@php
    $modes = config('hakeem.payment_modes');
    $modeLabels = collect($modes)->mapWithKeys(fn ($mode) => [$mode => __('admin.payments.mode_'.$mode)])->all();
@endphp

<x-layouts.admin :title="__('admin.payments.heading')">
    <x-page-header :title="__('admin.payments.heading')" :subtitle="__('admin.payments.subheading')"/>

    <form method="POST" action="{{ route('admin.payments.update') }}" class="max-w-3xl space-y-5">
        @csrf
        @method('PUT')

        <x-card :title="__('admin.payments.modes')" :subtitle="__('admin.payments.modes_hint')">
            <div class="space-y-2.5">
                @foreach ($modes as $mode)
                    <x-checkbox name="allowed_modes[]" :value="$mode" :label="$modeLabels[$mode]"
                                :checked="in_array($mode, old('allowed_modes', $allowedModes), true)"/>
                @endforeach
            </div>

            @error('allowed_modes')
                <p class="mt-2 text-xs font-medium text-danger-500">{{ $message }}</p>
            @enderror

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.payments.default_mode')" name="default_mode" required>
                    <x-select name="default_mode" :options="$modeLabels" :selected="$defaultMode"/>
                </x-field>
            </div>

            <div class="mt-4">
                <x-checkbox name="allow_clinic_override" :label="__('admin.payments.allow_clinic_override')"
                            :checked="$allowClinicOverride"/>
            </div>
        </x-card>

        <x-card :title="__('admin.payments.gateway')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.payments.gateway')" name="gateway" required>
                    <x-select name="gateway" :options="config('hakeem.payment_gateways')" :selected="$gateway"/>
                </x-field>

                <x-field :label="__('admin.payments.gateway_key')" name="gateway_key">
                    <x-input name="gateway_key" :value="$gatewayKey" dir="ltr" autocomplete="off"/>
                </x-field>

                <x-field :label="__('admin.payments.gateway_secret')" name="gateway_secret"
                         :hint="__('admin.payments.gateway_secret_hint')" class="sm:col-span-2">
                    <x-input name="gateway_secret" type="password" dir="ltr" autocomplete="new-password"
                             :placeholder="$hasGatewaySecret ? '••••••••' : ''"/>
                </x-field>
            </div>

            @if ($hasGatewaySecret)
                <p class="mt-3">
                    <x-badge tone="success">
                        <x-icon name="shield" class="size-3"/>
                        {{ __('admin.payments.secret_set') }}
                    </x-badge>
                </p>
            @endif

            <x-slot:footer>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
