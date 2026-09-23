<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\InsuranceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_token_is_provided(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/bookings')->assertUnauthorized();
    }

    public function test_phone_login_returns_a_token_and_user_payload(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'name' => 'مستخدم الجوال',
            'phone' => '201055512345',
            'password' => 'password',
        ]);
        $user->assignRole(RoleName::Patient);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '01055512345',
            'password' => 'password',
            'device_name' => 'pixel',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'مستخدم الجوال')
            ->assertJsonPath('user.phone_verified', false)
            ->assertJsonStructure(['token', 'user' => ['id', 'notify']]);
    }

    public function test_returns_422_when_login_password_is_wrong(): void
    {
        $this->seedRoles();
        User::factory()->create([
            'phone' => '201055512346',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '201055512346',
            'password' => 'nope',
        ])->assertUnprocessable();
    }

    public function test_device_registration_marks_the_app_as_installed(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['app_installed_at' => null]);
        $user->assignRole(RoleName::Patient);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices', [
                'platform' => 'android',
                'token' => 'fcm-token-1',
            ])
            ->assertOk()
            ->assertJsonPath('app_installed', true);

        $this->assertNotNull($user->fresh()->app_installed_at);
        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'platform' => 'android',
            'token' => 'fcm-token-1',
        ]);
    }

    public function test_patient_lists_and_cancels_own_booking_via_api(): void
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

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.id', $booking->id)
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->actingAs($patient, 'sanctum')
            ->deleteJson('/api/v1/bookings/'.$booking->id)
            ->assertOk()
            ->assertJsonPath('status', BookingStatus::Cancelled->value);

        $this->actingAs($patient, 'sanctum')
            ->deleteJson('/api/v1/bookings/'.$foreign->id)
            ->assertNotFound();
    }

    public function test_patient_creates_a_booking_through_the_api(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'doctor_id' => $provider['doctor']->id,
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::Pending->value);

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'doctor_id' => $provider['doctor']->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_returns_401_when_creating_a_booking_without_a_token(): void
    {
        $this->postJson('/api/v1/bookings', [])->assertUnauthorized();
    }

    public function test_patient_updates_profile_through_me(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $user = User::factory()->create(['name' => 'قبل التحديث']);
        $user->assignRole(RoleName::Patient);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me', [
                'name' => 'بعد التحديث',
                'preferred_language' => 'ar',
                'city_id' => $provider['city']->id,
                'notify_email' => false,
            ])
            ->assertOk()
            ->assertJsonPath('name', 'بعد التحديث')
            ->assertJsonPath('city_id', $provider['city']->id)
            ->assertJsonPath('notify.email', false);
    }

    public function test_api_register_stores_an_insurance_company(): void
    {
        $this->seedRoles();
        $provider = InsuranceProvider::factory()->create(['name_ar' => 'أكسا', 'name_en' => 'AXA']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'مريض الجوال',
            'phone' => '01055512347',
            'password' => 'secret-pass-1',
            'insurance_provider_id' => $provider->id,
        ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'مريض الجوال')
            ->assertJsonPath('user.insurance_provider_id', $provider->id)
            ->assertJsonPath('user.insurance_provider.name', 'أكسا');

        $this->assertDatabaseHas('users', [
            'name' => 'مريض الجوال',
            'insurance_provider_id' => $provider->id,
        ]);
    }

    public function test_returns_422_when_api_register_uses_an_inactive_insurance_company(): void
    {
        $this->seedRoles();
        $retired = InsuranceProvider::factory()->inactive()->create();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'مريض مرفوض',
            'phone' => '01055512348',
            'password' => 'secret-pass-1',
            'insurance_provider_id' => $retired->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('insurance_provider_id');

        $this->assertDatabaseMissing('users', ['name' => 'مريض مرفوض']);
    }

    public function test_patient_updates_insurance_through_me(): void
    {
        $this->seedRoles();
        $provider = InsuranceProvider::factory()->create();
        $user = User::factory()->create(['name' => 'قبل التأمين']);
        $user->assignRole(RoleName::Patient);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/me', [
                'name' => $user->name,
                'preferred_language' => 'ar',
                'insurance_provider_id' => $provider->id,
            ])
            ->assertOk()
            ->assertJsonPath('insurance_provider_id', $provider->id);

        $this->assertSame($provider->id, $user->fresh()->insurance_provider_id);
    }
}
