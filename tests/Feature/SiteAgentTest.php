<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_api_searches_doctors(): void
    {
        $provider = $this->seedListableProvider();

        $this->postJson('/api/v1/agent/messages', ['message' => 'سارة'])
            ->assertOk()
            ->assertJsonPath('intent', 'search_doctor')
            ->assertJsonPath('results.doctors.0.name', $provider['doctor']->name);
    }

    public function test_agent_requires_a_message(): void
    {
        $this->postJson('/api/v1/agent/messages', ['message' => ''])->assertUnprocessable();
    }

    public function test_guest_is_asked_to_sign_in_before_listing_appointments(): void
    {
        $this->postJson('/api/v1/agent/messages', ['message' => 'مواعيدي'])
            ->assertOk()
            ->assertJsonPath('intent', 'auth');
    }

    public function test_web_agent_cancels_an_open_booking_after_confirmation(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $booking = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $first = $this->actingAs($patient)
            ->postJson('/agent/messages', ['message' => 'إلغاء الحجز'])
            ->assertOk()
            ->assertJsonPath('intent', 'cancel_confirm');

        $this->actingAs($patient)
            ->postJson('/agent/messages', [
                'message' => 'نعم',
                'conversation_id' => $first->json('conversation_id'),
            ])
            ->assertOk()
            ->assertJsonPath('intent', 'cancelled');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }
}
