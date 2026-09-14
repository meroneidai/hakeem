<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name_ar', 'name_en', 'slug', 'is_active', 'display_order'])]
class Governorate extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('display_order')->orderBy('name_ar');
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
