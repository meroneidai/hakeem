<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ServiceTypeCode;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAndEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_psychiatric_visit_is_flagged_as_evaluation(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $service = ServiceType::query()->create([
            'code' => ServiceTypeCode::PsychiatricConsultation->value,
            'name_ar' => 'جلسة نفسية',
            'name_en' => 'Psychiatric consultation',
            'slug' => 'psychiatric-consultation-test',
            'is_active' => true,
        ]);
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $service->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertTrue(Booking::query()->where('patient_id', $patient->id)->first()->is_evaluation);
    }

    public function test_second_psychiatric_visit_is_not_an_evaluation(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $service = ServiceType::query()->create([
            'code' => ServiceTypeCode::PsychiatricConsultation->value,
            'name_ar' => 'جلسة نفسية',
            'name_en' => 'Psychiatric consultation',
            'slug' => 'psychiatric-consultation-test-2',
            'is_active' => true,
        ]);
        $patient = User::factory()->create();

        Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $service->id,
            'is_evaluation' => true,
            'status' => BookingStatus::Completed,
        ]);

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $service->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertFalse(
            Booking::query()->where('patient_id', $patient->id)->latest('id')->first()->is_evaluation
        );
    }

    public function test_patient_can_review_a_completed_booking_only(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $completed = Booking::factory()->completed()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        $this->actingAs($patient)
            ->from('/appointments')
            ->post('/appointments/'.$completed->id.'/review', [
                'overall' => 5,
                'body' => 'كشف منظم.',
            ])
            ->assertRedirect('/appointments')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $completed->id,
            'overall' => 5,
            'is_visible' => true,
        ]);

        $pending = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAs($patient)
            ->post('/appointments/'.$pending->id.'/review', ['overall' => 4])
            ->assertNotFound();
    }

    public function test_search_shows_verified_rating_after_a_review(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $booking = Booking::factory()->completed()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        Review::factory()->create([
            'booking_id' => $booking->id,
            'patient_id' => $patient->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_id' => $provider['clinic']->id,
            'overall' => 5,
            'is_visible' => true,
        ]);

        $this->getJson('/api/v1/search?q=&type=doctors&specialty='.$provider['specialty']->slug)
            ->assertOk()
            ->assertJsonPath('doctors.0.rating_average', 5)
            ->assertJsonPath('doctors.0.rating_count', 1);
    }
}
