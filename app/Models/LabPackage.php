<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name_ar', 'name_en', 'description_ar', 'description_en',
    'includes_ar', 'includes_en', 'conditions_ar', 'conditions_en',
    'preparation_ar', 'preparation_en', 'original_price', 'package_price',
    'image_path', 'display_order', 'is_featured', 'is_active',
])]
class LabPackage extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'original_price' => 'decimal:2',
            'package_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(LabTest::class, 'lab_package_test')->withTimestamps();
    }

    public function clinicOfferings(): HasMany
    {
        return $this->hasMany(ClinicLabOffering::class);
    }

    public function getIncludesAttribute(): ?string
    {
        return $this->translated('includes');
    }

    public function getConditionsAttribute(): ?string
    {
        return $this->translated('conditions');
    }

    public function getPreparationAttribute(): ?string
    {
        return $this->translated('preparation');
    }

    public function savings(): float
    {
        return max(0, (float) $this->original_price - (float) $this->package_price);
    }

    public function discountPercent(): int
    {
        if ((float) $this->original_price <= 0) {
            return 0;
        }

        return (int) round(($this->savings() / (float) $this->original_price) * 100);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name_ar');
    }
}
