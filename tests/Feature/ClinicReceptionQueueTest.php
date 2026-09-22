<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicReceptionQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_queue(): void
    {
        $this->get('/clinic/queue')->assertRedirect('/login');
    }

    public function test_patient_is_forbidden_from_the_queue(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->get('/clinic/queue')->assertForbidden();
    }

    public function test_reception_sees_today_bookings_for_their_clinic_only(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $patient = User::factory()->create(['name' => 'منى المريض']);
        $booking = $this->bookingFor($context, [
            'patient_id' => $patient->id,
            'scheduled_at' => now(),
        ]);

        $foreign = $this->foreignBooking($context);

        $this->actingAs($context['reception'])
            ->get('/clinic/queue')
            ->assertOk()
            ->assertSee('منى المريض')
            ->assertDontSee($foreign->patient->name);

        $this->assertSame($context['clinic']->id, $booking->clinic_id);
    }

    public function test_reception_confirms_a_pending_booking_and_notifies(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $booking = $this->bookingFor($context, ['scheduled_at' => now()]);

        $this->mock(NotificationDispatcher::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event, array $payload): bool => $event === 'booking_confirmed'
                && (int) $payload['booking_id'] === $booking->id);

        $this->actingAs($context['reception'])
            ->from('/clinic/queue')
            ->put('/clinic/queue/'.$booking->id, ['action' => 'confirm'])
            ->assertRedirect('/clinic/queue')
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'status' => BookingStatus::Confirmed->value,
            'changed_by_user_id' => $context['reception']->id,
        ]);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $booking = $this->bookingFor($context, ['scheduled_at' => now()]);

        $this->actingAs($context['reception'])
            ->from('/clinic/queue')
            ->put('/clinic/queue/'.$booking->id, ['action' => 'complete'])
            ->assertRedirect('/clinic/queue')
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_walk_in_creates_a_confirmed_booking_and_patient(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();

        $this->mock(NotificationDispatcher::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event): bool => $event === 'booking_confirmed');

        $this->actingAs($context['reception'])
            ->post('/clinic/queue', [
                'patient_name' => 'أحمد الزائر',
                'patient_phone' => '01012345678',
                'doctor_id' => $context['doctor']->id,
                'clinic_address_id' => $context['address']->id,
                'service_type_id' => $context['serviceType']->id,
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirectToRoute('clinic.queue.index', ['date' => now()->toDateString()])
            ->assertSessionHas('status');

        $patient = User::query()->where('phone', '201012345678')->first();

        $this->assertNotNull($patient);
        $this->assertTrue($patient->hasRole(RoleName::Patient));
        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'status' => BookingStatus::Confirmed->value,
            'payment_mode' => 'at_clinic',
        ]);
    }

    public function test_walk_in_stores_the_selected_payment_mode(): void
    {
        $this->freezeTime();

        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['at_clinic', 'after_service'],
            'payments.default_mode' => 'at_clinic',
        ], 'payments');

        $context = $this->queueContext();

        $this->actingAs($context['reception'])
            ->post('/clinic/queue', [
                'patient_name' => 'أحمد الزائر',
                'patient_phone' => '01012345678',
                'doctor_id' => $context['doctor']->id,
                'clinic_address_id' => $context['address']->id,
                'service_type_id' => $context['serviceType']->id,
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
                'payment_mode' => 'after_service',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'clinic_id' => $context['clinic']->id,
            'payment_mode' => 'after_service',
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_walk_in_reuses_an_existing_patient_by_phone(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $patient = User::factory()->create([
            'name' => 'مريض سابق',
            'phone' => '201055500000',
        ]);
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($context['reception'])
            ->post('/clinic/queue', [
                'patient_name' => 'اسم مختلف',
                'patient_phone' => '01055500000',
                'doctor_id' => $context['doctor']->id,
                'clinic_address_id' => $context['address']->id,
                'service_type_id' => $context['serviceType']->id,
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertSame(1, User::query()->where('phone', '201055500000')->count());
        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_walk_in_rejects_a_doctor_from_another_clinic(): void
    {
        $context = $this->queueContext();
        $otherDoctor = Doctor::factory()->create([
            'specialty_id' => $context['specialty']->id,
        ]);

        $this->actingAs($context['reception'])
            ->post('/clinic/queue', [
                'patient_name' => 'أحمد الزائر',
                'patient_phone' => '01012345678',
                'doctor_id' => $otherDoctor->id,
                'clinic_address_id' => $context['address']->id,
                'service_type_id' => $context['serviceType']->id,
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotFound();
    }

    public function test_updating_another_clinic_booking_is_not_found(): void
    {
        $context = $this->queueContext();
        $foreign = $this->foreignBooking($context);

        $this->actingAs($context['reception'])
            ->put('/clinic/queue/'.$foreign->id, ['action' => 'confirm'])
            ->assertNotFound();

        $this->assertSame(BookingStatus::Pending, $foreign->fresh()->status);
    }

    public function test_reception_reschedules_an_open_booking(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $booking = $this->bookingFor($context, ['scheduled_at' => now()->addHour()]);
        $next = now()->addDay()->format('Y-m-d H:i:s');

        $this->actingAs($context['reception'])
            ->from('/clinic/queue')
            ->put('/clinic/queue/'.$booking->id, [
                'action' => 'reschedule',
                'scheduled_at' => $next,
            ])
            ->assertRedirect('/clinic/queue');

        $this->assertSame($next, $booking->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_reception_marks_a_booking_paid(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $booking = $this->bookingFor($context, ['scheduled_at' => now()]);

        $this->actingAs($context['reception'])
            ->from('/clinic/queue')
            ->put('/clinic/queue/'.$booking->id, ['action' => 'mark_paid'])
            ->assertRedirect('/clinic/queue');

        $this->assertTrue($booking->fresh()->isPaid());
    }

    public function test_walk_in_requires_patient_doctor_branch_and_time(): void
    {
        $context = $this->queueContext();

        $this->actingAs($context['reception'])
            ->from('/clinic/queue/create')
            ->post('/clinic/queue', [])
            ->assertRedirect('/clinic/queue/create')
            ->assertSessionHasErrors(['patient_name', 'patient_phone', 'doctor_id', 'clinic_address_id', 'service_type_id', 'scheduled_at']);
    }

    public function test_queue_escapes_patient_names(): void
    {
        $this->freezeTime();

        $context = $this->queueContext();
        $patient = User::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->bookingFor($context, [
            'patient_id' => $patient->id,
            'scheduled_at' => now(),
        ]);

        $this->actingAs($context['reception'])
            ->get('/clinic/queue')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    /**
     * @return array{user: User, clinic: Clinic, reception: User, address: ClinicAddress, doctor: Doctor, serviceType: ServiceType, specialty: Specialty, city: City, plan: SubscriptionPlan, governorate: Governorate}
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
            'scheduled_at' => now(),
        ]);
    }
}
