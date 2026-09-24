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
        return collect($this->profileChecklist($user))
            ->where('required', true)
            ->every(fn (array $item) => $item['done']);
    }

    /**
     * Checklist of profile fields the patient should finish.
     *
     * @return list<array{key: string, label: string, hint: string, required: bool, done: bool, action: ?string, href: ?string}>
     */
    public function profileChecklist(User $user): array
    {
        $hasPhone = filled($user->phone);
        $hasEmail = filled($user->email);
        $hasCity = filled($user->city_id);

        return [
            [
                'key' => 'name',
                'label' => __('account.gaps.name'),
                'hint' => __('account.gaps.name_hint'),
                'required' => true,
                'done' => filled($user->name),
                'action' => null,
                'href' => filled($user->name) ? null : '#profile-form',
            ],
            [
                'key' => 'phone',
                'label' => __('account.gaps.phone'),
                'hint' => __('account.gaps.phone_hint'),
                'required' => true,
                'done' => $hasPhone,
                'action' => null,
                'href' => $hasPhone ? null : '#field-phone',
            ],
            [
                'key' => 'phone_verify',
                'label' => __('account.gaps.phone_verify'),
                'hint' => __('account.gaps.phone_verify_hint'),
                'required' => true,
                'done' => $hasPhone && $user->isPhoneVerified(),
                'action' => $hasPhone && ! $user->isPhoneVerified() ? 'phone' : null,
                'href' => $hasPhone ? null : '#field-phone',
            ],
            [
                'key' => 'city',
                'label' => __('account.gaps.city'),
                'hint' => __('account.gaps.city_hint'),
                'required' => true,
                'done' => $hasCity,
                'action' => null,
                'href' => $hasCity ? null : '#field-city',
            ],
            [
                'key' => 'email',
                'label' => __('account.gaps.email'),
                'hint' => __('account.gaps.email_hint'),
                'required' => false,
                'done' => $hasEmail,
                'action' => null,
                'href' => $hasEmail ? null : '#field-email',
            ],
            [
                'key' => 'email_verify',
                'label' => __('account.gaps.email_verify'),
                'hint' => __('account.gaps.email_verify_hint'),
                'required' => false,
                'done' => ! $hasEmail || $user->isEmailVerified(),
                'action' => $hasEmail && ! $user->isEmailVerified() ? 'email' : null,
                'href' => $hasEmail ? null : '#field-email',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, hint: string, required: bool, done: bool, action: ?string, href: ?string}>
     */
    public function profileGaps(User $user): array
    {
        return array_values(array_filter(
            $this->profileChecklist($user),
            fn (array $item) => ! $item['done'],
        ));
    }
}
