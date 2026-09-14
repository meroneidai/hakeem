<?php

namespace App\Enums;

use App\Models\SubscriptionPlan;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function labelAr(): string
    {
        return match ($this) {
            self::Monthly => 'شهري',
            self::Yearly => 'سنوي',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'ar' ? $this->labelAr() : $this->labelEn();
    }

    public function months(): int
    {
        return $this === self::Yearly ? 12 : 1;
    }

    /**
     * Amount charged for one period of this cycle on the given plan.
     */
    public function priceFor(SubscriptionPlan $plan): float
    {
        return $this === self::Yearly
            ? (float) $plan->effectiveYearlyPrice()
            : (float) $plan->monthly_price;
    }
}
