<?php

namespace App\Support;

use App\Models\User;

class Branding
{
    public function __construct(private Settings $settings) {}

    public function logoUrl(): ?string
    {
        return PublicImage::url($this->settings->get('branding.logo_path'));
    }

    public function faviconUrl(): ?string
    {
        return PublicImage::url($this->settings->get('branding.favicon_path'))
            ?: asset('favicon.ico');
    }

    public function tagline(): ?string
    {
        $locale = app()->getLocale();
        $key = $locale === 'en' ? 'branding.tagline_en' : 'branding.tagline_ar';

        return $this->settings->get($key) ?: $this->settings->get('branding.tagline_ar');
    }

    /**
     * @return array<string, string>
     */
    public function social(): array
    {
        $links = [];

        foreach (['facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'linkedin'] as $network) {
            $url = $this->settings->get('social.'.$network);

            if (filled($url)) {
                $links[$network] = $url;
            }
        }

        return $links;
    }

    public function profileComplete(User $user): bool
    {
        return filled($user->name)
            && filled($user->phone)
            && $user->phone_verified_at !== null
            && filled($user->city_id);
    }
}
