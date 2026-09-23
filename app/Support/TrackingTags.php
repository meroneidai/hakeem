<?php

namespace App\Support;

class TrackingTags
{
    public function __construct(private Settings $settings) {}

    public function gaMeasurementId(): ?string
    {
        return $this->validId($this->settings->get('analytics.ga_measurement_id'), '/^G-[A-Z0-9]+$/i');
    }

    public function gtmContainerId(): ?string
    {
        return $this->validId($this->settings->get('analytics.gtm_container_id'), '/^GTM-[A-Z0-9]+$/i');
    }

    public function googleAdsId(): ?string
    {
        return $this->validId($this->settings->get('analytics.google_ads_id'), '/^AW-[0-9]+$/');
    }

    public function searchConsoleVerification(): ?string
    {
        return $this->validId($this->settings->get('analytics.search_console_verification'), '/^[A-Za-z0-9_-]{8,100}$/');
    }

    public function isLive(): bool
    {
        return $this->gaMeasurementId() !== null
            || $this->gtmContainerId() !== null
            || $this->googleAdsId() !== null
            || $this->searchConsoleVerification() !== null;
    }

    /**
     * @return array<string, ?string>
     */
    public function values(): array
    {
        return [
            'analytics.ga_measurement_id' => $this->settings->get('analytics.ga_measurement_id'),
            'analytics.gtm_container_id' => $this->settings->get('analytics.gtm_container_id'),
            'analytics.google_ads_id' => $this->settings->get('analytics.google_ads_id'),
            'analytics.search_console_verification' => $this->settings->get('analytics.search_console_verification'),
        ];
    }

    private function validId(mixed $value, string $pattern): ?string
    {
        $id = is_string($value) ? trim($value) : '';

        if ($id === '' || preg_match($pattern, $id) !== 1) {
            return null;
        }

        return $id;
    }
}
