<?php

namespace App\Models;

use App\Enums\MedicalArticleCategory;
use App\Models\Concerns\HasTranslatedAttributes;
use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slug', 'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en',
    'body_ar', 'body_en', 'category', 'specialty_id', 'image_path',
    'is_published', 'published_at', 'display_order',
])]
class MedicalArticle extends Model
{
    use HasFactory, HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'category' => MedicalArticleCategory::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function getExcerptAttribute(): ?string
    {
        return $this->translated('excerpt');
    }

    public function getBodyAttribute(): ?string
    {
        return $this->translated('body');
    }

    public function imageUrl(): ?string
    {
        return PublicImage::url($this->image_path);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderByDesc('published_at')->orderByDesc('id');
    }
}
