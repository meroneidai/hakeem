<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\OfferCategory;
use App\Models\Booking;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_offer_page_includes_a_booking_form(): void
    {
        $offer = $this->runningOffer();

        $this->get('/offers/'.$offer->slug)
            ->assertOk()
            ->assertSee($offer->title_ar)
            ->assertSee(__('offers.book'))
            ->assertSee(__('booking.login_first'));
    }

    public function test_patient_books_a_running_offer(): void
    {
        $this->seedRoles();
        $offer = $this->runningOffer();
        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->post('/book/offers/'.$offer->slug, [
                'doctor_id' => $offer->clinic->doctors->first()->id,
                'clinic_address_id' => $offer->clinic->addresses->first()->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'session_count' => 2,
                'payment_mode' => 'at_clinic',
            ])
            ->assertRedirect('/appointments');

        $this->assertDatabaseHas('bookings', [
            'patient_id' => $patient->id,
            'clinic_id' => $offer->clinic_id,
            'promotion_id' => $offer->id,
            'session_count' => 2,
            'status' => BookingStatus::Pending->value,
        ]);

        $this->assertTrue(Booking::query()->where('promotion_id', $offer->id)->exists());
    }

    private function runningOffer(): Promotion
    {
        $provider = $this->seedListableProvider();

        return Promotion::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'title_ar' => 'عرض جديد',
            'title_en' => 'New offer',
            'slug' => 'aard-gdyd',
            'category' => OfferCategory::Checkup,
            'description_ar' => 'عرض يمكن حجزه.',
            'description_en' => 'A bookable offer.',
            'original_price' => 900,
            'offer_price' => 590,
            'session_count' => 1,
            'service_type_id' => $provider['serviceType']->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);
    }
}
