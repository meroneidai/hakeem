<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use App\Support\SafeHtml;
use Database\Factories\SitePageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug', 'path', 'is_system', 'is_published',
    'heading_ar', 'heading_en', 'intro_ar', 'intro_en',
    'body_ar', 'body_en', 'meta_title_ar', 'meta_title_en',
    'meta_description_ar', 'meta_description_en',
    'sitemap_priority', 'is_indexable',
])]
class SitePage extends Model
{
    /** @use HasFactory<SitePageFactory> */
    use HasFactory, HasTranslatedAttributes;

    /**
     * Built-in public pages whose slugs and paths cannot be changed.
     *
     * @var array<string, string>
     */
    public const SYSTEM_PATHS = [
        'home' => '/',
        'about' => '/about',
        'how-it-works' => '/how-it-works',
        'help' => '/help',
        'terms' => '/terms',
        'privacy' => '/privacy',
        'cookies' => '/cookies',
        'cancellation' => '/cancellation-policy',
        'disclaimer' => '/medical-disclaimer',
        'accessibility' => '/accessibility',
        'contact' => '/contact',
        'complaints' => '/complaints',
        'home-care' => '/home-care',
        'teleconsultation' => '/teleconsultation',
    ];

    /**
     * First URL segments that must not be claimed by a custom page.
     *
     * @var list<string>
     */
    public const RESERVED_SEGMENTS = [
        'admin', 'clinic', 'api', 'login', 'register', 'search', 'doctors', 'clinics',
        'specialties', 'services', 'cities', 'medical-library', 'how-it-works', 'about',
        'help', 'terms', 'privacy', 'cookies', 'cancellation-policy', 'medical-disclaimer',
        'accessibility', 'contact', 'complaints', 'book', 'appointments', 'account',
        'inbox', 'verify', 'labs', 'offers', 'password', 'auth', 'logout', 'locale',
        'home-care', 'teleconsultation', 'storage', 'build', 'vendor', 'livewire', 'up',
        'home',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_published' => 'boolean',
            'is_indexable' => 'boolean',
        ];
    }

    public function getHeadingAttribute(): ?string
    {
        return $this->translated('heading');
    }

    public function getIntroAttribute(): ?string
    {
        return $this->translated('intro');
    }

    public function getBodyAttribute(): ?string
    {
        return $this->translated('body');
    }

    public function getMetaTitleAttribute(): ?string
    {
        return $this->translated('meta_title');
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return $this->translated('meta_description');
    }

    public function renderedBody(): string
    {
        return SafeHtml::render($this->body);
    }

    public function publicUrl(): string
    {
        return url($this->path === '/' ? '/' : $this->path);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->where('is_indexable', true);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->where($field ?? $this->getRouteKeyName(), $value);

        if ($field === 'slug') {
            $query->published()->where('is_system', false);
        }

        return $query->firstOrFail();
    }

    /**
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return array_values(array_unique([
            ...array_keys(self::SYSTEM_PATHS),
            ...self::RESERVED_SEGMENTS,
        ]));
    }

    public static function pathForSlug(string $slug): string
    {
        return self::SYSTEM_PATHS[$slug] ?? '/'.$slug;
    }
}
