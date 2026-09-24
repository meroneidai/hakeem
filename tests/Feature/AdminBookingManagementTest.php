<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_confirm_and_complete_a_booking(): void
    {
        $this->freezeTime();

        $context = $this->bookingContext();
        $booking = Booking::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'patient_id' => User::factory(),
            'scheduled_at' => now(),
            'status' => BookingStatus::Pending,
        ]);

        $this->mock(NotificationDispatcher::class)->shouldIgnoreMissing();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->from('/admin/bookings/'.$booking->id)
            ->put('/admin/bookings/'.$booking->id, ['action' => 'confirm'])
            ->assertRedirect('/admin/bookings/'.$booking->id)
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        $this->put('/admin/bookings/'.$booking->id, ['action' => 'check_in'])->assertRedirect();
        $this->put('/admin/bookings/'.$booking->id, ['action' => 'complete'])->assertRedirect();

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_admin_can_create_a_booking_for_a_clinic(): void
    {
        $this->freezeTime();

        $context = $this->bookingContext();

        $this->mock(NotificationDispatcher::class)->shouldIgnoreMissing();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/bookings', [
            'clinic_id' => $context['clinic']->id,
            'patient_name' => 'مريض إداري',
            'patient_phone' => '01099887766',
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'scheduled_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_create_booking_form_renders_after_clinic_selected(): void
    {
        $context = $this->bookingContext();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/bookings/create?clinic='.$context['clinic']->id)
            ->assertOk()
            ->assertSee($context['clinic']->name_ar)
            ->assertSee(__('clinic.queue.patient_name'));
    }

    /**
     * @return array{clinic: Clinic, address: ClinicAddress, doctor: Doctor, serviceType: ServiceType}
     */
    private function bookingContext(): array
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

        return $context + compact('address', 'doctor');
    }
}
