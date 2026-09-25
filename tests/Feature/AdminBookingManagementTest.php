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
use App\Services\AddressScheduleWriter;
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

    public function test_admin_can_create_a_booking_for_an_existing_patient_by_phone(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $context = $this->bookingContext(withHours: true);
        $patient = User::factory()->create([
            'name' => 'مريض موجود',
            'phone' => '201099887766',
        ]);

        $this->mock(NotificationDispatcher::class)->shouldIgnoreMissing();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/bookings', [
            'clinic_id' => $context['clinic']->id,
            'patient_name' => $patient->name,
            'patient_phone' => '01099887766',
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'scheduled_at' => '2026-09-19 10:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'patient_id' => $patient->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
        $this->assertSame(1, User::query()->where('phone', '201099887766')->count());
    }

    public function test_create_booking_form_uses_phone_lookup_and_available_slots(): void
    {
        $context = $this->bookingContext();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/bookings/create?clinic='.$context['clinic']->id)
            ->assertOk()
            ->assertSee($context['clinic']->name_ar)
            ->assertSee(__('admin.bookings.search_phone'))
            ->assertSee(__('admin.bookings.lookup'))
            ->assertSee(__('booking.pick_slot'))
            ->assertSee(__('booking.when_hint'))
            ->assertDontSee('datetime-local', false);
    }

    public function test_admin_can_look_up_patient_by_phone(): void
    {
        $patient = User::factory()->create([
            'name' => 'سارة أحمد',
            'phone' => '201012345678',
        ]);

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->getJson('/admin/bookings/patients/lookup?phone=01012345678')
            ->assertOk()
            ->assertJson([
                'found' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => 'سارة أحمد',
                    'phone' => '201012345678',
                ],
            ]);

        $this->getJson('/admin/bookings/patients/lookup?phone=01000000000')
            ->assertOk()
            ->assertJson(['found' => false]);
    }

    public function test_patient_lookup_requires_admin_permission(): void
    {
        $this->getJson('/admin/bookings/patients/lookup?phone=01012345678')
            ->assertUnauthorized();

        $this->actingAsRole(RoleName::Patient);

        $this->getJson('/admin/bookings/patients/lookup?phone=01012345678')
            ->assertForbidden();
    }

    /**
     * @return array{clinic: Clinic, address: ClinicAddress, doctor: Doctor, serviceType: ServiceType}
     */
    private function bookingContext(bool $withHours = false): array
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

        if ($withHours) {
            app(AddressScheduleWriter::class)->seedDefaults($address);
            $address->load('schedules');
            $doctor->load('availability');
        }

        return $context + compact('address', 'doctor');
    }
}
