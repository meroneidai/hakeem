<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'patient_id' => User::factory(),
            'doctor_id' => Doctor::factory(),
            'clinic_id' => Clinic::factory(),
            'overall' => fake()->numberBetween(4, 5),
            'wait_time' => fake()->numberBetween(3, 5),
            'staff' => fake()->numberBetween(3, 5),
            'cleanliness' => fake()->numberBetween(3, 5),
            'body' => fake()->optional()->sentence(),
            'is_visible' => true,
        ];
    }
}
