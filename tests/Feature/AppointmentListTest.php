<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_appointments(): void
    {
        $this->get('/appointments')->assertRedirect('/login');
    }

    public function test_patient_sees_only_their_appointments(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $other = User::factory()->create();
        $otherDoctor = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'طبيب آخر فريد',
            'name_en' => 'Other Unique Doctor',
        ]);
        $provider['clinic']->doctors()->attach($otherDoctor);

        Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        Booking::factory()->create([
            'patient_id' => $other->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $otherDoctor->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($patient)
            ->get('/appointments')
            ->assertOk()
            ->assertSee('noindex,nofollow')
            ->assertSee($provider['doctor']->name)
            ->assertDontSee('طبيب آخر فريد');
    }

    public function test_appointments_escape_clinic_names(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider([
            'clinic_name_ar' => '<script>alert(1)</script>',
            'clinic_name_en' => 'Safe Clinic',
        ]);
        $patient = User::factory()->create();

        Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        $this->actingAs($patient)
            ->get('/appointments')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_patient_does_not_see_bookings_from_an_unrelated_clinic_address(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $otherAddress = ClinicAddress::factory()->create([
            'city_id' => $provider['city']->id,
        ]);

        Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        $this->actingAs($patient)
            ->get('/appointments')
            ->assertOk()
            ->assertDontSee($otherAddress->address_line);
    }
}
