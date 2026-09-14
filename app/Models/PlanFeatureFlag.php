<?php

namespace App\Models;

use App\Enums\PlanFeature;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['subscription_plan_id', 'feature_code', 'is_enabled'])]
class PlanFeatureFlag extends Model
{
    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function enum(): ?PlanFeature
    {
        return PlanFeature::tryFrom($this->feature_code);
    }
}
