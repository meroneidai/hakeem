<?php

namespace App\Support;

use App\Models\SitePage;

/**
 * Resolves public page copy from admin CMS rows, falling back to language files.
 */
class SiteCopy
{
    /** @var array<string, ?SitePage> */
    private array $pages = [];

    public function page(string $slug): ?SitePage
    {
        if (! array_key_exists($slug, $this->pages)) {
            $this->pages[$slug] = SitePage::query()->published()->where('slug', $slug)->first();
        }

        return $this->pages[$slug];
    }

    public function heading(string $slug, string $fallbackKey): string
    {
        $value = $this->page($slug)?->heading;

        return filled($value) ? $value : (string) __($fallbackKey);
    }

    public function intro(string $slug, string $fallbackKey): string
    {
        $value = $this->page($slug)?->intro;

        return filled($value) ? $value : (string) __($fallbackKey);
    }

    public function body(string $slug): ?string
    {
        $value = $this->page($slug)?->body;

        return filled($value) ? $value : null;
    }

    public function metaTitle(string $slug, string $fallbackKey): string
    {
        $page = $this->page($slug);

        if (filled($page?->meta_title)) {
            return (string) $page->meta_title;
        }

        return $this->heading($slug, $fallbackKey);
    }

    public function metaDescription(string $slug, ?string $fallbackKey = null): ?string
    {
        $page = $this->page($slug);

        if (filled($page?->meta_description)) {
            return $page->meta_description;
        }

        if (filled($page?->intro)) {
            return $page->intro;
        }

        return $fallbackKey ? (string) __($fallbackKey) : null;
    }
}
