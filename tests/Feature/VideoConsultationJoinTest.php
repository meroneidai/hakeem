<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Enums\ServiceTypeCode;
use App\Models\ClinicService;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoConsultationJoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_booking_issues_a_video_room_and_opens_after_confirmation(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $service = ServiceType::query()->create([
            'code' => ServiceTypeCode::VideoConsultation->value,
            'name_ar' => 'استشارة بالفيديو',
            'name_en' => 'Video Consultation',
            'slug' => 'video-consultation-test',
            'is_active' => true,
            'is_online' => true,
        ]);
        ClinicService::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'service_type_id' => $service->id,
            'price' => 400,
            'is_active' => true,
        ]);
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $service->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect('/appointments');

        $booking = $patient->bookings()->first();

        $this->assertNotNull($booking?->video_room_token);

        $this->actingAs($patient)
            ->get('/appointments/'.$booking->id.'/video')
            ->assertOk()
            ->assertSee(__('booking.video.waiting'))
            ->assertDontSee('meet.jit.si', false);

        $booking->update(['status' => BookingStatus::Confirmed]);

        $this->actingAs($patient)
            ->get('/appointments/'.$booking->id.'/video')
            ->assertOk()
            ->assertSee('meet.jit.si/hakeem-'.$booking->video_room_token, false)
            ->assertSee(__('booking.egypt_time'));
    }

    public function test_another_patient_cannot_open_the_video_room(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $service = ServiceType::query()->create([
            'code' => ServiceTypeCode::VideoConsultation->value,
            'name_ar' => 'استشارة بالفيديو',
            'name_en' => 'Video Consultation',
            'slug' => 'video-consultation-foreign',
            'is_active' => true,
            'is_online' => true,
        ]);
        $patient = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($patient)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $service->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect('/appointments');

        $booking = $patient->bookings()->first();

        $this->actingAs($stranger)
            ->get('/appointments/'.$booking->id.'/video')
            ->assertNotFound();
    }
}
