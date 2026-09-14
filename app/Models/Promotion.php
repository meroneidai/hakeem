<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'title_ar', 'title_en', 'slug', 'description_ar', 'description_en',
    'discount_type', 'discount_value', 'discount_details', 'banner_image_path',
    'specialty_id', 'service_type_id', 'starts_at', 'ends_at',
    'is_featured', 'is_active', 'created_by_user_id',
])]
class Promotion extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isPlatformWide(): bool
    {
        return $this->clinic_id === null;
    }

    public function isRunning(): bool
    {
        return $this->is_active
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }

    public function status(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $this->ends_at->isPast() ? 'expired' : 'running';
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
