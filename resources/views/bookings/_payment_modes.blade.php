<x-field :label="__('booking.payment')" name="payment_mode" required>
    <div class="space-y-2">
        @foreach ($paymentModes as $mode)
            <label
                class="flex items-start gap-3 rounded-xl border border-ink-200 p-3 has-[:checked]:border-primary-400 has-[:checked]:bg-primary-50"
                @if (! empty($filterPaymentsByService))
                    x-show="modeAllowed(@js($mode->value))"
                    x-cloak
                @endif
            >
                <input
                    type="radio"
                    name="payment_mode"
                    value="{{ $mode->value }}"
                    class="mt-1 size-4 border-ink-300 text-primary-600 focus:ring-primary-300"
                    @checked((string) old('payment_mode', $defaultPaymentMode?->value) === $mode->value)
                    @if (! empty($filterPaymentsByService))
                        :disabled="! modeAllowed(@js($mode->value))"
                    @endif
                >
                <span>
                    <span class="block text-sm font-medium text-ink-900">{{ $mode->label() }}</span>
                    <span class="mt-0.5 block text-xs text-ink-500">{{ $mode->hint() }}</span>
                </span>
            </label>
        @endforeach
        @if (! empty($filterPaymentsByService))
            <p class="text-xs text-ink-500" x-show="visibleModeCount() === 0" x-cloak>{{ __('booking.payment_none_for_service') }}</p>
        @endif
    </div>

    @if (! $gatewayReady)
        <p class="mt-2 text-xs text-ink-500">{{ __('booking.online_gateway_later') }}</p>
    @endif
</x-field>
