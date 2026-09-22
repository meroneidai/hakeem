<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_booking_form(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/book/doctors/'.$provider['doctor']->slug)
            ->assertRedirect('/login');
    }

    public function test_patient_creates_a_pending_booking_request(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => $scheduledAt,
                'notes' => 'كشف متابعة',
            ])
            ->assertRedirect('/appointments')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'status' => BookingStatus::Pending->value,
            'notes' => 'كشف متابعة',
        ]);

        $this->assertDatabaseHas('booking_status_history', [
            'status' => BookingStatus::Pending->value,
            'changed_by_user_id' => $patient->id,
        ]);

        $this->assertSame(
            PaymentMode::AtClinic,
            Booking::query()->where('patient_id', $patient->id)->first()->payment_mode,
        );
    }

    public function test_patient_can_choose_an_allowed_payment_mode(): void
    {
        $this->seedRoles();
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['at_clinic', 'after_service'],
            'payments.default_mode' => 'at_clinic',
        ], 'payments');

        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->get('/book/doctors/'.$provider['doctor']->slug)
            ->assertOk()
            ->assertSee(__('booking.payment'))
            ->assertSee(__('booking.payment_mode.after_service'));

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => 'after_service',
            ])
            ->assertRedirect('/appointments');

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'payment_mode' => 'after_service',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_booking_rejects_a_payment_mode_the_clinic_does_not_offer(): void
    {
        $this->seedRoles();
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['at_clinic'],
            'payments.default_mode' => 'at_clinic',
        ], 'payments');

        $provider = $this->seedListableProvider();

        $this->actingAs(User::factory()->create())
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'payment_mode' => 'online',
            ])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors('payment_mode');
    }

    public function test_booking_rejects_an_address_from_another_clinic(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $other = ClinicAddress::factory()->create([
            'city_id' => $provider['city']->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $other->id,
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertNotFound();
    }

    public function test_inactive_doctor_booking_is_not_found(): void
    {
        $provider = $this->seedListableProvider(['doctor_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/book/doctors/'.$provider['doctor']->slug)
            ->assertNotFound();
    }

    public function test_booking_requires_service_branch_and_time(): void
    {
        $provider = $this->seedListableProvider();

        $this->actingAs(User::factory()->create())
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors(['service_type_id', 'clinic_address_id', 'scheduled_at']);
    }
}
