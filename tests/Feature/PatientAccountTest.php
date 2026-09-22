<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_patient_saves_profile_and_sees_incomplete_banner_until_phone_is_verified(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create([
            'name' => 'مريض الملف',
            'phone_verified_at' => null,
            'city_id' => null,
        ]);
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient)
            ->get('/')
            ->assertOk()
            ->assertSee(__('account.incomplete'));

        $this->actingAs($patient)
            ->from('/account')
            ->put('/account', [
                'name' => 'مريض الملف',
                'preferred_language' => 'ar',
                'city_id' => $provider['city']->id,
                'notify_email' => 1,
                'notify_sms' => 1,
                'notify_push' => 1,
            ])
            ->assertRedirect('/account')
            ->assertSessionHas('status');

        $this->assertSame($provider['city']->id, $patient->fresh()->city_id);

        $this->actingAs($patient->fresh())
            ->get('/')
            ->assertOk()
            ->assertSee(__('account.incomplete'));
    }

    public function test_patient_profile_persists_birth_date_gender_and_city(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create([
            'date_of_birth' => null,
            'gender' => null,
            'city_id' => null,
        ]);
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient)
            ->from('/account')
            ->put('/account', [
                'name' => $patient->name,
                'preferred_language' => 'ar',
                'email' => $patient->email,
                'phone' => $patient->phone,
                'birth_day' => 15,
                'birth_month' => 5,
                'birth_year' => 1990,
                'gender' => 'female',
                'city_id' => $provider['city']->id,
                'notify_email' => 1,
                'notify_sms' => 1,
                'notify_push' => 1,
            ])
            ->assertRedirect('/account')
            ->assertSessionHas('status');

        $patient->refresh();

        $this->assertSame('1990-05-15', $patient->date_of_birth?->format('Y-m-d'));
        $this->assertSame('female', $patient->gender);
        $this->assertSame($provider['city']->id, $patient->city_id);

        $this->actingAs($patient)
            ->get('/account')
            ->assertOk()
            ->assertSee(__('account.saved_birth', ['date' => '1990-05-15']));
    }

    public function test_patient_cancels_an_open_appointment_and_cannot_cancel_another_patients(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $other = User::factory()->create();

        $booking = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $foreign = Booking::factory()->create([
            'patient_id' => $other->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        $this->actingAs($patient)
            ->from('/appointments')
            ->delete('/appointments/'.$booking->id)
            ->assertRedirect('/appointments')
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);

        $this->actingAs($patient)
            ->delete('/appointments/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_booking_creates_an_inbox_row_for_the_patient(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $patient->id,
            'event' => 'booking_created',
        ]);

        $this->actingAs($patient)
            ->getJson('/inbox')
            ->assertOk()
            ->assertJsonPath('unread', 1);

        $notification = InAppNotification::query()->where('user_id', $patient->id)->first();

        $this->actingAs($patient)
            ->putJson('/inbox/'.$notification->id)
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
