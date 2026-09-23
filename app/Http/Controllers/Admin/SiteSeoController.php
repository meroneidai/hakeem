<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\PublicImage;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class SiteSeoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSeoPages->value];
    }

    public function edit(Settings $settings): View
    {
        return view('admin.seo-pages.site', [
            'values' => [
                'seo.site_title_ar' => $settings->get('seo.site_title_ar'),
                'seo.site_title_en' => $settings->get('seo.site_title_en'),
                'seo.default_description_ar' => $settings->get('seo.default_description_ar'),
                'seo.default_description_en' => $settings->get('seo.default_description_en'),
                'seo.keywords_ar' => $settings->get('seo.keywords_ar'),
                'seo.keywords_en' => $settings->get('seo.keywords_en'),
                'seo.og_image' => $settings->get('seo.og_image'),
                'seo.twitter_site' => $settings->get('seo.twitter_site'),
                'seo.app_ios_url' => $settings->get('seo.app_ios_url'),
                'seo.app_android_url' => $settings->get('seo.app_android_url'),
                'general.support_whatsapp' => $settings->get('general.support_whatsapp'),
                'general.support_phone' => $settings->get('general.support_phone'),
                'general.support_email' => $settings->get('general.support_email'),
                'branding.logo_path' => $settings->get('branding.logo_path'),
                'branding.favicon_path' => $settings->get('branding.favicon_path'),
                'branding.tagline_ar' => $settings->get('branding.tagline_ar'),
                'branding.tagline_en' => $settings->get('branding.tagline_en'),
                'social.facebook' => $settings->get('social.facebook'),
                'social.instagram' => $settings->get('social.instagram'),
                'social.twitter' => $settings->get('social.twitter'),
                'social.youtube' => $settings->get('social.youtube'),
                'social.tiktok' => $settings->get('social.tiktok'),
                'social.linkedin' => $settings->get('social.linkedin'),
            ],
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'seo.site_title_ar' => ['nullable', 'string', 'max:70'],
            'seo.site_title_en' => ['nullable', 'string', 'max:70'],
            'seo.default_description_ar' => ['nullable', 'string', 'max:320'],
            'seo.default_description_en' => ['nullable', 'string', 'max:320'],
            'seo.keywords_ar' => ['nullable', 'string', 'max:320'],
            'seo.keywords_en' => ['nullable', 'string', 'max:320'],
            'seo.og_image' => ['nullable', 'url', 'max:500'],
            'seo.twitter_site' => ['nullable', 'string', 'max:64'],
            'seo.app_ios_url' => ['nullable', 'url', 'max:500'],
            'seo.app_android_url' => ['nullable', 'url', 'max:500'],
            'general.support_whatsapp' => ['nullable', 'string', 'max:32'],
            'general.support_phone' => ['nullable', 'string', 'max:32'],
            'general.support_email' => ['nullable', 'email', 'max:190'],
            'branding.tagline_ar' => ['nullable', 'string', 'max:190'],
            'branding.tagline_en' => ['nullable', 'string', 'max:190'],
            'logo' => PublicImage::rules(),
            'favicon' => PublicImage::faviconRules(),
            'social.facebook' => ['nullable', 'url', 'max:500'],
            'social.instagram' => ['nullable', 'url', 'max:500'],
            'social.twitter' => ['nullable', 'url', 'max:500'],
            'social.youtube' => ['nullable', 'url', 'max:500'],
            'social.tiktok' => ['nullable', 'url', 'max:500'],
            'social.linkedin' => ['nullable', 'url', 'max:500'],
        ]);

        $seo = $data['seo'] ?? [];
        $general = $data['general'] ?? [];
        $branding = $data['branding'] ?? [];
        $social = $data['social'] ?? [];

        $settings->setMany([
            'seo.site_title_ar' => $seo['site_title_ar'] ?? null,
            'seo.site_title_en' => $seo['site_title_en'] ?? null,
            'seo.default_description_ar' => $seo['default_description_ar'] ?? null,
            'seo.default_description_en' => $seo['default_description_en'] ?? null,
            'seo.keywords_ar' => $seo['keywords_ar'] ?? null,
            'seo.keywords_en' => $seo['keywords_en'] ?? null,
            'seo.og_image' => $seo['og_image'] ?? null,
            'seo.twitter_site' => $seo['twitter_site'] ?? null,
            'seo.app_ios_url' => $seo['app_ios_url'] ?? null,
            'seo.app_android_url' => $seo['app_android_url'] ?? null,
        ], 'seo');

        $settings->setMany([
            'general.support_whatsapp' => $general['support_whatsapp'] ?? null,
            'general.support_phone' => $general['support_phone'] ?? null,
            'general.support_email' => $general['support_email'] ?? null,
        ], 'general');

        $logoPath = PublicImage::store($request, 'logo', 'branding', $settings->get('branding.logo_path'));
        $faviconPath = PublicImage::store($request, 'favicon', 'branding', $settings->get('branding.favicon_path'));

        $settings->setMany([
            'branding.logo_path' => $logoPath,
            'branding.favicon_path' => $faviconPath,
            'branding.tagline_ar' => $branding['tagline_ar'] ?? null,
            'branding.tagline_en' => $branding['tagline_en'] ?? null,
        ], 'branding');

        $settings->setMany([
            'social.facebook' => $social['facebook'] ?? null,
            'social.instagram' => $social['instagram'] ?? null,
            'social.twitter' => $social['twitter'] ?? null,
            'social.youtube' => $social['youtube'] ?? null,
            'social.tiktok' => $social['tiktok'] ?? null,
            'social.linkedin' => $social['linkedin'] ?? null,
        ], 'social');

        Audit::log('seo.site_updated');

        return back()->with('status', __('common.updated_successfully'));
    }
}
