<x-layouts.clinic :title="__('clinic.payments.heading')">
    <x-page-header :title="__('clinic.payments.heading')" :subtitle="__('clinic.payments.subtitle')"/>

    @if (! $mayOverride)
        <x-alert tone="warning" class="mb-5">{{ __('clinic.payments.locked') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('clinic.payments.update') }}" class="max-w-xl">
        @csrf
        @method('PUT')

        <x-card>
            @php
                $selectedModeValues = collect(old('payment_modes', $allowedModes))
                    ->map(fn ($mode) => $mode instanceof \App\Enums\PaymentMode ? $mode->value : $mode)
                    ->all();
            @endphp
            <div class="space-y-2.5">
                @foreach ($platformModes as $mode)
                    <x-checkbox
                        name="payment_modes[]"
                        :value="$mode->value"
                        :label="$mode->label()"
                        :hint="$mode->hint()"
                        :checked="in_array($mode->value, $selectedModeValues, true)"
                        :disabled="! $mayOverride"
                    />
                @endforeach
            </div>

            @error('payment_modes')
                <p class="mt-2 text-xs font-medium text-danger-500">{{ $message }}</p>
            @enderror

            <div class="mt-5">
                <x-field :label="__('clinic.payments.default')" name="default_payment_mode" required>
                    <x-select
                        name="default_payment_mode"
                        :options="collect($platformModes)->mapWithKeys(fn ($mode) => [$mode->value => $mode->label()])->all()"
                        :selected="$defaultMode->value"
                        :disabled="! $mayOverride"
                    />
                </x-field>
            </div>

            @unless ($gatewayReady)
                <p class="mt-4 text-xs text-ink-500">{{ __('booking.online_gateway_later') }}</p>
            @endunless

            @if ($mayOverride)
                <x-slot:footer>
                    <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
                </x-slot:footer>
            @endif
        </x-card>
    </form>
</x-layouts.clinic>
