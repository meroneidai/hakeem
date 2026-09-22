<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicBookingsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_dashboard_shows_colored_today_analytics(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $this->bookingFor($context, [
            'scheduled_at' => now(),
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($context['user'])
            ->get('/clinic')
            ->assertOk()
            ->assertSee(__('clinic.dashboard.today_pending'))
            ->assertSee(__('clinic.dashboard.today_flow'))
            ->assertSee(__('clinic.dashboard.next_visits'));
    }

    public function test_reception_does_not_see_setup_hint_on_the_dashboard(): void
    {
        $context = $this->queueContext();

        $this->actingAs($context['reception'])
            ->get('/clinic')
            ->assertOk()
            ->assertDontSee(__('clinic.dashboard.pending_hint'), false)
            ->assertSee(__('clinic.dashboard.today_flow'));
    }

    public function test_reception_sees_full_booking_history_for_their_clinic_only(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $patient = User::factory()->create(['name' => 'منى السجل']);
        $this->bookingFor($context, [
            'patient_id' => $patient->id,
            'scheduled_at' => now()->subDay(),
        ]);

        $foreign = $this->foreignBooking($context);

        $this->actingAs($context['reception'])
            ->get('/clinic/bookings')
            ->assertOk()
            ->assertSee('منى السجل')
            ->assertDontSee($foreign->patient->name);
    }

    public function test_doctor_only_sees_their_own_bookings(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $doctorUser = User::factory()->create();
        $doctorUser->assignRole(RoleName::Doctor, $context['clinic']->id);

        $ownDoctor = Doctor::factory()->create([
            'specialty_id' => $context['specialty']->id,
            'user_id' => $doctorUser->id,
            'name_ar' => 'طبيبة العيادة',
        ]);
        $context['clinic']->doctors()->attach($ownDoctor);

        $ownPatient = User::factory()->create(['name' => 'مريض الطبيبة']);
        $this->bookingFor($context, [
            'patient_id' => $ownPatient->id,
            'doctor_id' => $ownDoctor->id,
            'scheduled_at' => now(),
        ]);

        $otherPatient = User::factory()->create(['name' => 'مريض زميل']);
        $this->bookingFor($context, [
            'patient_id' => $otherPatient->id,
            'scheduled_at' => now(),
        ]);

        $this->actingAs($doctorUser)
            ->get('/clinic/bookings')
            ->assertOk()
            ->assertSee('مريض الطبيبة')
            ->assertDontSee('مريض زميل');

        $this->actingAs($doctorUser)
            ->get('/clinic/queue')
            ->assertOk()
            ->assertSee('مريض الطبيبة')
            ->assertDontSee('مريض زميل');
    }

    /**
     * @return array{user: User, clinic: Clinic, reception: User, address: ClinicAddress, doctor: Doctor, serviceType: ServiceType, specialty: Specialty}
     */
    private function queueContext(): array
    {
        $context = $this->actingAsClinicOwner();

        $address = ClinicAddress::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'city_id' => $context['city']->id,
        ]);

        $doctor = Doctor::factory()->create([
            'specialty_id' => $context['specialty']->id,
        ]);
        $context['clinic']->doctors()->attach($doctor);

        $reception = User::factory()->create();
        $reception->assignRole(RoleName::Reception, $context['clinic']->id);

        return $context + compact('address', 'doctor', 'reception');
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     */
    private function bookingFor(array $context, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'patient_id' => User::factory(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function foreignBooking(array $context): Booking
    {
        $otherOwner = User::factory()->create();
        $otherClinic = Clinic::factory()->create([
            'owner_user_id' => $otherOwner->id,
            'subscription_plan_id' => $context['plan']->id,
        ]);
        $otherAddress = ClinicAddress::factory()->create([
            'clinic_id' => $otherClinic->id,
            'city_id' => $context['city']->id,
        ]);
        $otherDoctor = Doctor::factory()->create([
            'specialty_id' => $context['specialty']->id,
        ]);
        $otherClinic->doctors()->attach($otherDoctor);

        return Booking::factory()->create([
            'clinic_id' => $otherClinic->id,
            'doctor_id' => $otherDoctor->id,
            'clinic_address_id' => $otherAddress->id,
            'service_type_id' => $context['serviceType']->id,
            'patient_id' => User::factory()->create(['name' => 'مريض عيادة أخرى'])->id,
            'scheduled_at' => now()->subDay(),
        ]);
    }
}
