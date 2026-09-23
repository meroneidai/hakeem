<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\User;
use App\Services\AddressScheduleWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_cannot_double_book_the_same_doctor_slot(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertRedirect('/appointments');

        $this->actingAs($second)
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors('scheduled_at');

        $this->assertSame(1, Booking::query()->where('doctor_id', $provider['doctor']->id)->count());
    }

    public function test_booking_outside_branch_hours_is_rejected(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());

        $this->actingAs(User::factory()->create())
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => '2026-09-19 20:00:00',
            ])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_slots_endpoint_lists_open_times_and_hides_taken_ones(): void
    {
        $this->travelTo('2026-09-19 08:00:00');

        $this->seedRoles();
        $provider = $this->seedHours($this->seedListableProvider());
        $patient = User::factory()->create();

        $this->getJson('/doctors/'.$provider['doctor']->slug.'/slots?'.http_build_query([
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'date' => '2026-09-19',
        ]))
            ->assertOk()
            ->assertJsonFragment(['starts_at' => '2026-09-19 10:00:00']);

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => '2026-09-19 10:00:00',
            ])
            ->assertRedirect('/appointments');

        $this->getJson('/doctors/'.$provider['doctor']->slug.'/slots?'.http_build_query([
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'date' => '2026-09-19',
        ]))
            ->assertOk()
            ->assertJsonMissing(['starts_at' => '2026-09-19 10:00:00'])
            ->assertJsonFragment(['starts_at' => '2026-09-19 10:30:00']);
    }

    /**
     * @param  array{address: ClinicAddress}  $provider
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
