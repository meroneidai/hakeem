<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InsuranceProvider>
 */
class InsuranceProviderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = fake()->unique()->company().' Insurance';

        return [
            'name_ar' => 'شركة تأمين '.fake()->unique()->numerify('###'),
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn),
            'hotline' => '16'.fake()->numerify('###'),
            'is_active' => true,
            'display_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
