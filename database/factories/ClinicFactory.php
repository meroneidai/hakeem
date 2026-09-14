<?php

namespace Database\Factories;

use App\Enums\VerificationStatus;
use App\Models\Clinic;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    public function definition(): array
    {
        $nameEn = fake()->unique()->company().' Clinic';

        return [
            'owner_user_id' => User::factory(),
            'name_ar' => 'عيادة '.fake()->unique()->numerify('###'),
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn).'-'.fake()->unique()->numerify('###'),
            'email' => fake()->unique()->companyEmail(),
            'phone' => '2010'.fake()->numerify('########'),
            'is_single_doctor' => true,
            'subscription_plan_id' => SubscriptionPlan::query()->where('is_default_free', true)->value('id'),
            'verification_status' => VerificationStatus::Pending,
            'is_active' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]);
    }
}
