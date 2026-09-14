<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'subscription_plan_id', 'discount_code_id', 'billing_cycle',
    'status', 'amount', 'current_period_start', 'current_period_end', 'cancelled_at',
])]
class ClinicSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'status' => SubscriptionStatus::class,
            'amount' => 'decimal:2',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function isFree(): bool
    {
        return (float) $this->amount === 0.0;
    }

    /**
     * Free plans have no renewal date, so they never read as expiring.
     */
    public function isExpiring(int $withinDays = 7): bool
    {
        if ($this->isFree() || $this->current_period_end === null) {
            return false;
        }

        return $this->current_period_end->isBefore(now()->addDays($withinDays));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }
}
