<?php

namespace App\Support;

use App\Models\User;

class Branding
{
    private const DEFAULT_TOP_LOGO = 'images/logo/Hakeem-logo-top.png';

    private const DEFAULT_FOOTER_LOGO = 'images/logo/Hakeem-logo-footer.png';

    public function __construct(private Settings $settings) {}

    public function name(): string
    {
        $key = app()->getLocale() === 'en' ? 'seo.site_title_en' : 'seo.site_title_ar';
        $value = $this->settings->get($key) ?: $this->settings->get('seo.site_title_ar');

        return filled($value) ? (string) $value : (string) __('common.app_name');
    }

    public function titled(?string $pageTitle): string
    {
        $brand = $this->name();

        if (! filled($pageTitle) || $pageTitle === $brand) {
            return $brand;
        }

        return str_contains($pageTitle, $brand) ? $pageTitle : $pageTitle.' — '.$brand;
    }

    public function description(): ?string
    {
        $key = app()->getLocale() === 'en' ? 'seo.default_description_en' : 'seo.default_description_ar';
        $value = $this->settings->get($key) ?: $this->settings->get('seo.default_description_ar');

        return filled($value) ? (string) $value : null;
    }

    public function keywords(): ?string
    {
        $key = app()->getLocale() === 'en' ? 'seo.keywords_en' : 'seo.keywords_ar';
        $value = $this->settings->get($key) ?: $this->settings->get('seo.keywords_ar');

        return filled($value) ? (string) $value : null;
    }

    public function logoUrl(): string
    {
        return PublicImage::url($this->settings->get('branding.logo_path'))
            ?: asset(self::DEFAULT_TOP_LOGO);
    }

    public function footerLogoUrl(): string
    {
        return PublicImage::url($this->settings->get('branding.footer_logo_path'))
            ?: asset(self::DEFAULT_FOOTER_LOGO);
    }

    public function usesCustomLogo(): bool
    {
        return filled($this->settings->get('branding.logo_path'));
    }

    public function usesCustomFooterLogo(): bool
    {
        return filled($this->settings->get('branding.footer_logo_path'));
    }

    public function faviconUrl(): string
    {
        return PublicImage::url($this->settings->get('branding.favicon_path'))
            ?: asset(self::DEFAULT_TOP_LOGO);
    }

    public function shareImageUrl(): ?string
    {
        $og = $this->settings->get('seo.og_image');

        if (is_string($og) && filled($og)) {
            return $og;
        }

        return $this->logoUrl();
    }

    public function tagline(): ?string
    {
        $locale = app()->getLocale();
        $key = $locale === 'en' ? 'branding.tagline_en' : 'branding.tagline_ar';

        return $this->settings->get($key) ?: $this->settings->get('branding.tagline_ar') ?: __('common.app_tagline');
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

    public function appIosUrl(): ?string
    {
        $url = $this->settings->get('seo.app_ios_url');

        return filled($url) ? (string) $url : null;
    }

    public function appAndroidUrl(): ?string
    {
        $url = $this->settings->get('seo.app_android_url');

        return filled($url) ? (string) $url : null;
    }

    public function agentChatEnabled(string $surface = 'web'): bool
    {
        return match ($surface) {
            'mobile' => $this->settings->bool('features.agent_chat_mobile', true),
            default => $this->settings->bool('features.agent_chat_web', true),
        };
    }

    public function agentChatAvailable(): bool
    {
        return $this->agentChatEnabled('web') || $this->agentChatEnabled('mobile');
    }

    public function showDemoLogins(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        return $this->settings->bool('general.show_demo_logins', true);
    }

    public function organizationId(): string
    {
        return url('/').'#organization';
    }

    public function profileComplete(User $user): bool
    {
        return filled($user->name)
            && filled($user->phone)
            && $user->phone_verified_at !== null
            && filled($user->city_id);
    }
}
