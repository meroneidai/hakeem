<?php

namespace App\Support;

use App\Models\SeoPage;
use App\Models\SitePage;
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
        public ?string $keywords = null,
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
        $branding = app(Branding::class);
        $path = '/'.ltrim($request->path(), '/');
        $path = $path === '/.' ? '/' : $path;
        $sitePage = SitePage::query()->published()->where('path', $path)->first();
        $page = $sitePage ? null : SeoPage::query()->where('path', $path)->first();

        $resolvedTitle = filled($sitePage?->meta_title)
            ? $branding->titled($sitePage->meta_title)
            : (filled($page?->meta_title)
                ? $branding->titled($page->meta_title)
                : $branding->titled($sitePage?->heading ?: $title));

        $resolvedDescription = filled($sitePage?->meta_description)
            ? $sitePage->meta_description
            : (filled($page?->meta_description)
                ? $page->meta_description
                : ($sitePage?->intro ?: ($description ?: $branding->description())));

        if (($sitePage && ! $sitePage->is_indexable) || ($page && ! $page->is_indexable)) {
            $robots = 'noindex,follow';
        }

        $canonical = url($path === '/' ? '/' : $path);
        $ogImage = $image ?: $branding->shareImageUrl();

        $jsonLd = self::enrichGraphs($jsonLd, $resolvedTitle, $resolvedDescription, $canonical, $path);

        return new self(
            title: $resolvedTitle,
            description: $resolvedDescription,
            canonical: $canonical,
            robots: $robots,
            image: filled($ogImage) ? $ogImage : null,
            ogType: $ogType,
            jsonLd: $jsonLd,
            h1: $sitePage?->heading ?: $page?->translated('h1'),
            intro: $sitePage?->intro ?: $page?->translated('intro_content'),
            keywords: $branding->keywords(),
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
        $branding = app(Branding::class);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $branding->name(),
            'alternateName' => [__('common.app_name'), 'Hakeem'],
            'url' => url('/'),
            'inLanguage' => [app()->getLocale() === 'en' ? 'en-EG' : 'ar-EG'],
            'description' => $description ?: $branding->description(),
            'publisher' => [
                '@id' => $branding->organizationId(),
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function organizationGraph(): array
    {
        $branding = app(Branding::class);
        $support = app(SupportLinks::class);
        $logo = $branding->shareImageUrl();
        $sameAs = array_values($branding->social());
        $twitter = app(Settings::class)->get('seo.twitter_site');

        if (is_string($twitter) && filled($twitter)) {
            $handle = ltrim($twitter, '@');
            $sameAs[] = 'https://x.com/'.$handle;
        }

        $sameAs = array_values(array_unique(array_filter($sameAs)));
        $contactPoints = [];

        if ($support->telephone()) {
            $contactPoints[] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $support->telephone(),
                'areaServed' => 'EG',
                'availableLanguage' => ['ar', 'en'],
            ];
        }

        if ($support->email()) {
            $contactPoints[] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $support->email(),
                'areaServed' => 'EG',
                'availableLanguage' => ['ar', 'en'],
            ];
        }

        if ($support->hasWhatsapp()) {
            $contactPoints[] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $support->whatsappTelephone(),
                'url' => $support->whatsappUrl(),
                'areaServed' => 'EG',
                'availableLanguage' => ['ar', 'en'],
            ];
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'MedicalOrganization',
            '@id' => $branding->organizationId(),
            'name' => $branding->name(),
            'legalName' => $branding->name(),
            'alternateName' => __('common.app_name'),
            'url' => url('/'),
            'description' => $branding->description(),
            'slogan' => $branding->tagline(),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo,
            ],
            'image' => $logo,
            'email' => $support->email(),
            'telephone' => $support->telephone(),
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'EG',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'EG',
            ],
            'contactPoint' => $contactPoints !== [] ? $contactPoints : null,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function contactPageGraph(): array
    {
        $branding = app(Branding::class);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => __('pages.contact.heading'),
            'url' => url('/contact'),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $branding->name(),
                'url' => url('/'),
            ],
            'about' => [
                '@id' => $branding->organizationId(),
            ],
        ];
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
                'name' => app(Branding::class)->name(),
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
            'og:site_name' => app(Branding::class)->name(),
            'og:image' => $this->image,
            'twitter:card' => $this->image ? 'summary_large_image' : 'summary',
            'twitter:title' => $this->title,
            'twitter:description' => $this->description,
            'twitter:image' => $this->image,
            'twitter:site' => app(Settings::class)->get('seo.twitter_site'),
        ], fn ($value) => filled($value));
    }
}
