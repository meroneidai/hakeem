<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_type', 'path', 'governorate_id', 'city_id', 'specialty_id', 'service_type_id',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
    'h1_ar', 'h1_en', 'intro_content_ar', 'intro_content_en',
    'is_indexable', 'sitemap_priority',
])]
class SeoPage extends Model
{
    use HasTranslatedAttributes;

    public const PAGE_TYPES = [
        'governorate',
        'city',
        'specialty',
        'city_specialty',
        'governorate_specialty',
        'service',
        'custom',
    ];

    protected function casts(): array
    {
        return ['is_indexable' => 'boolean'];
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function getMetaTitleAttribute(): ?string
    {
        return $this->translated('meta_title');
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return $this->translated('meta_description');
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->where('is_indexable', true);
    }
}
