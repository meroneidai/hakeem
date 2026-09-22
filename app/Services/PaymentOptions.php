<?php

namespace App\Services;

use App\Enums\PaymentMode;
use App\Models\Clinic;
use App\Support\Settings;

class PaymentOptions
{
    public function __construct(private Settings $settings) {}

    /**
     * @return list<PaymentMode>
     */
    public function platformModes(): array
    {
        $modes = $this->hydrate($this->settings->get('payments.allowed_modes', [PaymentMode::AtClinic->value]));

        return $modes === [] ? [PaymentMode::AtClinic] : $modes;
    }

    /**
     * Modes a patient may choose for this clinic. Clinic overrides are a subset
     * of the platform list; an empty or stale override falls back to platform.
     *
     * @return list<PaymentMode>
     */
    public function allowedModes(?Clinic $clinic = null): array
    {
        $platform = $this->platformModes();

        if (! $clinic || ! $this->clinicMayOverride()) {
            return $platform;
        }

        $override = $this->hydrate($clinic->payment_modes ?? []);
        $allowed = array_values(array_filter(
            $override,
            fn (PaymentMode $mode) => in_array($mode, $platform, true),
        ));

        return $allowed === [] ? $platform : $allowed;
    }

    public function defaultMode(?Clinic $clinic = null): PaymentMode
    {
        $allowed = $this->allowedModes($clinic);

        $candidates = [];

        if ($clinic && $this->clinicMayOverride() && filled($clinic->default_payment_mode)) {
            $candidates[] = PaymentMode::tryFrom((string) $clinic->default_payment_mode);
        }

        $candidates[] = PaymentMode::tryFrom((string) $this->settings->get('payments.default_mode', PaymentMode::AtClinic->value));

        foreach ($candidates as $candidate) {
            if ($candidate && in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $allowed[0] ?? PaymentMode::AtClinic;
    }

    /**
     * @return list<string>
     */
    public function allowedValues(?Clinic $clinic = null): array
    {
        return array_map(fn (PaymentMode $mode) => $mode->value, $this->allowedModes($clinic));
    }

    public function allows(?Clinic $clinic, PaymentMode $mode): bool
    {
        return in_array($mode, $this->allowedModes($clinic), true);
    }

    public function clinicMayOverride(): bool
    {
        return $this->settings->bool('payments.allow_clinic_override', true);
    }

    public function isGatewayConfigured(): bool
    {
        $gateway = (string) $this->settings->get('payments.gateway', 'none');

        return $gateway !== 'none' && filled($this->settings->get('payments.gateway_key'));
    }

    /**
     * @param  list<string>|mixed  $values
     * @return list<PaymentMode>
     */
    private function hydrate(mixed $values): array
    {
        if (! is_array($values) || $values === []) {
            return [];
        }

        $modes = [];

        foreach ($values as $value) {
            $mode = PaymentMode::tryFrom((string) $value);

            if ($mode && ! in_array($mode, $modes, true)) {
                $modes[] = $mode;
            }
        }

        return $modes;
    }
}
