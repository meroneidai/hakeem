<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\PatientAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientAddress>
 */
class PatientAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'المنزل',
            'line' => 'شارع التحرير، مدينة نصر',
            'city_id' => City::query()->value('id'),
            'latitude' => 30.0444000,
            'longitude' => 31.2357000,
            'is_default' => true,
        ];
    }
}
