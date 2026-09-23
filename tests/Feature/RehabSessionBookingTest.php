<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ClinicService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RehabSessionBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_physio_visit_at_a_clinic_is_an_evaluation(): void
    {
        $context = $this->physioProvider();
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->post('/book/doctors/'.$context['doctor']->slug, [
                'service_type_id' => $context['serviceType']->id,
                'clinic_address_id' => $context['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'session_count' => 8,
            ])
            ->assertRedirect('/appointments');

        $booking = Booking::query()->first();

        $this->assertTrue($booking->is_evaluation);
        $this->assertSame(8, $booking->session_count);
    }

    public function test_clinic_cannot_confirm_a_follow_up_without_a_completed_evaluation(): void
    {
        $context = $this->physioProvider();
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->post('/book/doctors/'.$context['doctor']->slug, [
                'service_type_id' => $context['serviceType']->id,
                'clinic_address_id' => $context['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

        $evaluation = Booking::query()->first();

        $this->actingAs($patient)
            ->post('/book/doctors/'.$context['doctor']->slug, [
                'service_type_id' => $context['serviceType']->id,
                'clinic_address_id' => $context['address']->id,
                'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'session_count' => 4,
            ]);

        $followUp = Booking::query()->where('id', '!=', $evaluation->id)->first();

        $this->assertFalse($followUp->is_evaluation);
        $this->assertSame(BookingStatus::Pending, $followUp->status);

        $this->actingAs($context['owner'])
            ->from('/clinic/queue')
            ->put('/clinic/queue/'.$followUp->id, ['action' => 'confirm'])
            ->assertRedirect('/clinic/queue')
            ->assertSessionHasErrors('status');

        $this->assertSame(BookingStatus::Pending, $followUp->fresh()->status);

        $this->actingAs($context['owner'])
            ->put('/clinic/queue/'.$evaluation->id, ['action' => 'confirm'])
            ->assertRedirect();

        $evaluation->update(['status' => BookingStatus::Completed]);

        $this->actingAs($context['owner'])
            ->put('/clinic/queue/'.$followUp->id, ['action' => 'confirm'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Confirmed, $followUp->fresh()->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function physioProvider(): array
    {
        $provider = $this->seedListableProvider();
        $provider['specialty']->update(['category' => 'physical_therapy']);
        $provider['doctor']->update(['specialty_id' => $provider['specialty']->id]);

        ClinicService::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'service_type_id' => $provider['serviceType']->id,
            'specialty_id' => $provider['specialty']->id,
            'price' => 280,
            'duration_minutes' => 45,
            'session_count' => 8,
            'is_active' => true,
            'requires_evaluation_first' => true,
        ]);

        return $provider + ['owner' => $provider['clinic']->owner];
    }
}
