<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'service_type_id', 'specialty_id',
    'price', 'promo_price', 'duration_minutes', 'is_active',
])]
class ClinicService extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promo_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Price a patient actually pays, honouring an active promo price.
     */
    public function effectivePrice(): float
    {
        return $this->hasPromo() ? (float) $this->promo_price : (float) $this->price;
    }

    public function hasPromo(): bool
    {
        return $this->promo_price !== null && (float) $this->promo_price < (float) $this->price;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
