<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicAddress>
 */
class ClinicAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'city_id' => City::query()->value('id') ?? City::factory(),
            'label_ar' => 'الفرع الرئيسي',
            'label_en' => 'Main branch',
            'address_line' => fake()->streetAddress(),
            'is_primary' => true,
            'is_active' => true,
        ];
    }
}
