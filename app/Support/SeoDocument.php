<?php

namespace App\Support;

use App\Models\SeoPage;
use Illuminate\Http\Request;

class SeoDocument
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public string $robots,
        public ?string $image,
        public string $ogType,
        public array $jsonLd,
        public ?string $h1 = null,
        public ?string $intro = null,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    public static function make(
        Request $request,
        ?string $title = null,
        ?string $description = null,
        string $robots = 'index,follow',
        ?string $image = null,
        string $ogType = 'website',
        array $jsonLd = [],
    ): self {
        $settings = app(Settings::class);
        $locale = app()->getLocale();
        $path = '/'.ltrim($request->path(), '/');
        $path = $path === '/.' ? '/' : $path;
        $page = SeoPage::query()->where('path', $path)->first();

        $defaultDescription = $locale === 'en'
            ? $settings->get('seo.default_description_en')
            : $settings->get('seo.default_description_ar');

        $brand = __('common.app_name');
        if (filled($page?->meta_title)) {
            $resolvedTitle = $page->meta_title;
        } elseif (filled($title)) {
            $resolvedTitle = str_contains($title, $brand) ? $title : $title.' — '.$brand;
        } else {
            $resolvedTitle = $brand;
        }

        $resolvedDescription = filled($page?->meta_description)
            ? $page->meta_description
            : ($description ?: (is_string($defaultDescription) ? $defaultDescription : null));

        if ($page && ! $page->is_indexable) {
            $robots = 'noindex,follow';
        }

        $canonical = url($path === '/' ? '/' : $path);
        $ogImage = $image ?: (is_string($settings->get('seo.og_image')) ? $settings->get('seo.og_image') : null);

        $jsonLd = self::enrichGraphs($jsonLd, $resolvedTitle, $resolvedDescription, $canonical, $path);

        return new self(
            title: $resolvedTitle,
            description: $resolvedDescription,
            canonical: $canonical,
            robots: $robots,
            image: filled($ogImage) ? $ogImage : null,
            ogType: $ogType,
            jsonLd: $jsonLd,
            h1: $page?->translated('h1'),
            intro: $page?->translated('intro_content'),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     * @return list<array<string, mixed>>
     */
    private static function enrichGraphs(
        array $jsonLd,
        string $title,
        ?string $description,
        string $canonical,
        string $path,
    ): array {
        if (! self::hasType($jsonLd, 'WebSite')) {
            array_unshift($jsonLd, self::websiteGraph($title, $description));
        }

        if (! self::hasType($jsonLd, 'MedicalOrganization', 'Organization')) {
            $jsonLd[] = self::organizationGraph();
        }

        if (! self::hasType($jsonLd, 'WebPage', 'MedicalWebPage', 'CollectionPage')) {
            $jsonLd[] = self::webPageGraph($title, $description, $canonical);
        }

        if (! self::hasType($jsonLd, 'BreadcrumbList')) {
            $crumbs = self::defaultCrumbs($title, $canonical, $path);
            if (count($crumbs) > 1) {
                $jsonLd[] = self::breadcrumbs($crumbs);
            }
        }

        return $jsonLd;
    }

    /**
     * @param  list<array<string, mixed>>  $jsonLd
     */
    private static function hasType(array $jsonLd, string ...$types): bool
    {
        return collect($jsonLd)->contains(fn ($node) => in_array($node['@type'] ?? null, $types, true));
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    private static function defaultCrumbs(string $title, string $canonical, string $path): array
    {
        $crumbs = [
            ['name' => __('discover.nav.home'), 'url' => url('/')],
        ];

        if ($path === '/') {
            return $crumbs;
        }

        $label = str_contains($title, ' — ') ? explode(' — ', $title)[0] : $title;
        $crumbs[] = ['name' => $label, 'url' => $canonical];

        return $crumbs;
    }

    /**
     * @return array<string, mixed>
     */
    public static function websiteGraph(?string $title = null, ?string $description = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => __('common.app_name'),
            'url' => url('/'),
            'inLanguage' => [app()->getLocale() === 'en' ? 'en-EG' : 'ar-EG'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => __('common.app_name'),
                'url' => url('/'),
            ],
            'description' => $description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organizationGraph(): array
    {
        $settings = app(Settings::class);
        $support = app(SupportLinks::class);
        $twitter = $settings->get('seo.twitter_site');
        $sameAs = [];

        if (is_string($twitter) && filled($twitter)) {
            $sameAs[] = 'https://x.com/'.ltrim($twitter, '@');
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'MedicalOrganization',
            'name' => __('common.app_name'),
            'url' => url('/'),
            'logo' => is_string($settings->get('seo.og_image')) && filled($settings->get('seo.og_image'))
                ? $settings->get('seo.og_image')
                : url('/favicon.ico'),
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'EG',
            ],
            'telephone' => $support->telephone(),
            'email' => $support->email(),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function webPageGraph(string $title, ?string $description, string $canonical): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => __('common.app_name'),
                'url' => url('/'),
            ],
            'inLanguage' => app()->getLocale() === 'en' ? 'en-EG' : 'ar-EG',
        ]);
    }

    /**
     * @param  list<array{name: string, url: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function openGraph(): array
    {
        $locale = app()->getLocale() === 'en' ? 'en_EG' : 'ar_EG';
        $alternate = app()->getLocale() === 'en' ? 'ar_EG' : 'en_EG';

        return array_filter([
            'og:title' => $this->title,
            'og:description' => $this->description,
            'og:url' => $this->canonical,
            'og:type' => $this->ogType,
            'og:locale' => $locale,
            'og:locale:alternate' => $alternate,
            'og:site_name' => __('common.app_name'),
            'og:image' => $this->image,
            'twitter:card' => $this->image ? 'summary_large_image' : 'summary',
            'twitter:title' => $this->title,
            'twitter:description' => $this->description,
            'twitter:image' => $this->image,
            'twitter:site' => app(Settings::class)->get('seo.twitter_site'),
        ], fn ($value) => filled($value));
    }
}
