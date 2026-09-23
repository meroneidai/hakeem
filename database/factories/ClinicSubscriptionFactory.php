<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\InvoicePaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Clinic;
use App\Models\ClinicSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicSubscription>
 */
class ClinicSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'subscription_plan_id' => SubscriptionPlan::query()->value('id'),
            'billing_cycle' => BillingCycle::Monthly,
            'status' => SubscriptionStatus::Active,
            'payment_status' => InvoicePaymentStatus::Pending,
            'amount' => 1500,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->addMonth()->startOfMonth(),
            'due_at' => now()->addMonth()->startOfMonth(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => InvoicePaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
