<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'clinic_id' => Clinic::factory(),
            'doctor_id' => Doctor::factory(),
            'clinic_address_id' => ClinicAddress::factory(),
            'service_type_id' => ServiceType::query()->value('id'),
            'scheduled_at' => now()->addHour(),
            'status' => BookingStatus::Pending,
            'payment_mode' => PaymentMode::AtClinic,
            'payment_status' => 'unpaid',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Completed]);
    }
}
