<?php

namespace App\Support;

class SupportLinks
{
    public function __construct(private Settings $settings) {}

    public function whatsappUrl(): string
    {
        $digits = $this->internationalDigits($this->settings->get('general.support_whatsapp'));

        return filled($digits) ? 'https://wa.me/'.$digits : route('contact');
    }

    public function phoneUrl(): string
    {
        $digits = $this->internationalDigits($this->settings->get('general.support_phone'));

        return filled($digits) ? 'tel:+'.$digits : route('contact');
    }

    public function hasWhatsapp(): bool
    {
        return filled($this->internationalDigits($this->settings->get('general.support_whatsapp')));
    }

    public function hasPhone(): bool
    {
        return filled($this->internationalDigits($this->settings->get('general.support_phone')));
    }

    public function telephone(): ?string
    {
        $digits = $this->internationalDigits($this->settings->get('general.support_phone'));

        return filled($digits) ? '+'.$digits : null;
    }

    public function email(): ?string
    {
        $email = $this->settings->get('general.support_email');

        return filled($email) && is_string($email) ? $email : null;
    }

    private function internationalDigits(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        }

        return $digits !== '' ? $digits : null;
    }
}
