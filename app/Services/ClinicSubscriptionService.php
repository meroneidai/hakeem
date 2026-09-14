<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Clinic;
use App\Models\ClinicSubscription;
use App\Models\DiscountCode;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class ClinicSubscriptionService
{
    /**
     * Attach a plan to the clinic and open a new billing period. Previous
     * subscriptions are cancelled. Online payment is a stub until Phase 9 —
     * the period is marked active even on paid plans so onboarding is never
     * blocked on a gateway.
     */
    public function subscribe(
        Clinic $clinic,
        SubscriptionPlan $plan,
        BillingCycle $cycle = BillingCycle::Monthly,
        ?DiscountCode $discount = null,
    ): ClinicSubscription {
        return DB::transaction(function () use ($clinic, $plan, $cycle, $discount) {
            $clinic->subscriptions()
                ->where('status', SubscriptionStatus::Active->value)
                ->update([
                    'status' => SubscriptionStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            $amount = $cycle->priceFor($plan);

            if ($discount?->isRedeemable($plan)) {
                $amount = $discount->applyTo($amount);
                $discount->increment('times_used');
            } else {
                $discount = null;
            }

            $isFree = $amount <= 0 || $plan->is_default_free;

            $subscription = $clinic->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'discount_code_id' => $discount?->id,
                'billing_cycle' => $cycle,
                'status' => SubscriptionStatus::Active,
                'amount' => $amount,
                'current_period_start' => now(),
                'current_period_end' => $isFree ? null : now()->addMonths($cycle->months()),
            ]);

            $clinic->update(['subscription_plan_id' => $plan->id]);

            return $subscription;
        });
    }
}
