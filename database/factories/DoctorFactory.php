<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $nameEn = fake()->unique()->name();

        return [
            'name_ar' => 'د. '.fake()->firstName(),
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn).'-'.fake()->unique()->numerify('###'),
            'specialty_id' => Specialty::query()->value('id'),
            'is_active' => true,
        ];
    }
}
