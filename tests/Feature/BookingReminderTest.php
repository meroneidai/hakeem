<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_confirmed_visits_in_the_next_day(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);

        $due = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addHours(3),
        ]);

        $later = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Confirmed,
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->artisan('bookings:send-reminders')
            ->assertSuccessful()
            ->expectsOutputToContain('Sent 1 booking reminder');

        $this->assertNotNull($due->fresh()->reminder_sent_at);
        $this->assertNull($later->fresh()->reminder_sent_at);

        $this->artisan('bookings:send-reminders')
            ->expectsOutputToContain('Sent 0 booking reminder');
    }
}
