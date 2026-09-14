<?php

namespace App\Models;

use App\Enums\PlanFeature;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
    'monthly_price', 'yearly_price', 'yearly_discount_pct',
    'booking_cap', 'doctor_cap', 'address_cap',
    'is_default_free', 'is_active', 'display_order',
])]
class SubscriptionPlan extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'is_default_free' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(PlanFeatureFlag::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class);
    }

    public function allows(PlanFeature|string $feature): bool
    {
        $code = $feature instanceof PlanFeature ? $feature->value : $feature;

        return (bool) $this->featureFlags
            ->firstWhere('feature_code', $code)?->is_enabled;
    }

    /**
     * Applies the plan's own yearly discount to arrive at the effective yearly price.
     */
    public function effectiveYearlyPrice(): float
    {
        if ($this->yearly_price > 0) {
            return (float) $this->yearly_price;
        }

        $full = (float) $this->monthly_price * 12;

        return round($full * (1 - $this->yearly_discount_pct / 100), 2);
    }

    public function hasUnlimitedBookings(): bool
    {
        return $this->booking_cap === null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('monthly_price');
    }
}
