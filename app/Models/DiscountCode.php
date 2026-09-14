<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'code', 'description', 'discount_type', 'discount_value', 'subscription_plan_id',
    'valid_from', 'valid_to', 'max_uses', 'times_used', 'is_active',
])]
class DiscountCode extends Model
{
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isRedeemable(?SubscriptionPlan $plan = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        if ($this->subscription_plan_id && $plan && $this->subscription_plan_id !== $plan->id) {
            return false;
        }

        return true;
    }

    public function applyTo(float $amount): float
    {
        $discounted = $this->discount_type === 'percentage'
            ? $amount * (1 - (float) $this->discount_value / 100)
            : $amount - (float) $this->discount_value;

        return round(max($discounted, 0), 2);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
