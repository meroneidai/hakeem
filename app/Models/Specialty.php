<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name_ar', 'name_en', 'slug', 'category', 'description_ar', 'description_en',
    'icon', 'is_active', 'is_featured', 'display_order',
])]
class Specialty extends Model
{
    use HasTranslatedAttributes;

    public const CATEGORIES = [
        'general',
        'dental',
        'cosmetic',
        'beauty',
        'physical_therapy',
        'psychiatry',
        'laboratory',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name_ar');
    }
}
