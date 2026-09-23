<?php

namespace App\Models;

use App\Enums\LabTestCategory;
use App\Enums\SampleType;
use App\Models\Concerns\HasTranslatedAttributes;
use App\Support\PublicImage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name_ar', 'name_en', 'category', 'description_ar', 'description_en',
    'measures_ar', 'measures_en', 'preparation_ar', 'preparation_en',
    'contains_ar', 'contains_en', 'sample_type', 'fasting_hours',
    'turnaround_hours', 'suggested_price', 'image_path', 'display_order', 'is_active',
])]
class LabTest extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'category' => LabTestCategory::class,
            'sample_type' => SampleType::class,
            'suggested_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(LabPackage::class, 'lab_package_test')->withTimestamps();
    }

    public function clinicOfferings(): HasMany
    {
        return $this->hasMany(ClinicLabOffering::class);
    }

    public function getMeasuresAttribute(): ?string
    {
        return $this->translated('measures');
    }

    public function getPreparationAttribute(): ?string
    {
        return $this->translated('preparation');
    }

    public function getContainsAttribute(): ?string
    {
        return $this->translated('contains');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name_ar');
    }

    public function imageUrl(): string
    {
        return PublicImage::url($this->image_path) ?: $this->fallbackImageUrl();
    }

    public function fallbackImageUrl(): string
    {
        $file = 'images/labs/'.$this->category->value.'.svg';

        if (is_file(public_path($file))) {
            return asset($file);
        }

        return asset('images/labs/default.svg');
    }
}
