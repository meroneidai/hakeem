<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\ServiceTypeCode;
use App\Models\Booking;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\AddressScheduleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentBookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_work_without_ids_and_return_options(): void
    {
        $this->travelTo('2026-09-19 08:00:00');
        $provider = $this->seedHours($this->seedListableProvider());

        $response = $this->hermes()
            ->getJson('/api/agent/v1/doctors/'.$provider['doctor']->slug.'/slots')
            ->assertOk()
            ->assertJsonPath('resolved.doctor_id', $provider['doctor']->id)
            ->assertJsonPath('resolved.clinic_address_id', $provider['address']->id)
            ->assertJsonPath('resolved.service_type_id', $provider['serviceType']->id);

        $this->assertNotEmpty($response->json('options.addresses'));
        $this->assertNotEmpty($response->json('options.service_types'));
        $this->assertTrue(collect($response->json('options.service_types'))
            ->contains(fn (array $row) => array_key_exists('requires_patient_address', $row)));
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_booking_requires_customer_credentials(): void
    {
        $this->travelTo('2026-09-19 08:00:00');
        $provider = $this->seedHours($this->seedListableProvider());

        $this->hermes()
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'customer_credentials_required');
    }

    public function test_authenticated_customer_can_book_with_slug_only(): void
    {
        $this->travelTo('2026-09-19 08:00:00');
        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $token = $patient->createToken('hermes')->plainTextToken;

        $this->hermes(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('booking.doctor', $provider['doctor']->name);

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'doctor_id' => $provider['doctor']->id,
        ]);
    }

    public function test_home_visit_requires_patient_address(): void
    {
        $this->travelTo('2026-09-19 08:00:00');
        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());
        $homeVisit = ServiceType::query()->create([
            'code' => ServiceTypeCode::HomeVisit->value,
            'name_ar' => 'زيارة منزلية',
            'name_en' => 'Home visit',
            'slug' => 'home-visit-agent',
            'requires_patient_address' => true,
            'requires_clinic_address' => false,
            'requires_time_slot' => true,
            'is_active' => true,
            'display_order' => 2,
            'default_duration_minutes' => 30,
        ]);

        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $token = $patient->createToken('hermes')->plainTextToken;

        $this->hermes(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'service_type_id' => $homeVisit->id,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient_home_address');

        $this->hermes(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'service_type_id' => $homeVisit->id,
                'scheduled_at' => '2026-09-19 10:30:00',
                'patient_home_address' => 'شارع الهرم، الجيزة',
            ])
            ->assertCreated()
            ->assertJsonPath('ok', true);
    }

    public function test_taken_slot_returns_validation_error_not_server_error(): void
    {
        $this->travelTo('2026-09-19 08:00:00');
        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());
        $first = User::factory()->create();
        $first->assignRole(RoleName::Patient);
        $second = User::factory()->create();
        $second->assignRole(RoleName::Patient);

        $this->hermes(['Authorization' => 'Bearer '.$first->createToken('hermes')->plainTextToken])
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertCreated();

        $this->hermes(['Authorization' => 'Bearer '.$second->createToken('hermes')->plainTextToken])
            ->postJson('/api/agent/v1/bookings', [
                'doctor_slug' => $provider['doctor']->slug,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_at');

        $this->assertSame(1, Booking::query()->where('doctor_id', $provider['doctor']->id)->count());
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function hermes(array $headers = []): static
    {
        return $this->withHeaders(array_merge([
            'X-Hermes-Key' => 'testing-hermes-key',
        ], $headers));
    }

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    private function seedHours(array $provider): array
    {
        app(AddressScheduleWriter::class)->seedDefaults($provider['address']);
        $provider['address']->load('schedules');
        $provider['doctor']->load('availability');

        return $provider;
    }
}
