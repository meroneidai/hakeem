<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'item_type', 'lab_test_id', 'lab_package_id',
    'price', 'promo_price', 'allows_home_collection', 'is_active',
])]
class ClinicLabOffering extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promo_price' => 'decimal:2',
            'allows_home_collection' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function labPackage(): BelongsTo
    {
        return $this->belongsTo(LabPackage::class);
    }

    public function effectivePrice(): float
    {
        return $this->promo_price !== null ? (float) $this->promo_price : (float) $this->price;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Offerings patients can actually book: active, at a listable lab, optionally in a place.
     */
    public function scopeForListableLabs(Builder $query, ?string $governorate = null, ?string $city = null): Builder
    {
        return $query->active()->whereHas('clinic', function (Builder $clinic) use ($governorate, $city) {
            $clinic->listable()
                ->when($governorate, fn (Builder $labs, string $slug) => $labs->whereHas(
                    'addresses.city.governorate',
                    fn (Builder $gov) => $gov->where('slug', $slug)
                ))
                ->when($city, fn (Builder $labs, string $slug) => $labs->whereHas(
                    'addresses.city',
                    fn (Builder $cityQuery) => $cityQuery->where('slug', $slug)
                ));
        });
    }
}
