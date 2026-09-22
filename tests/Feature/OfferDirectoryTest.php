<?php

namespace Tests\Feature;

use App\Enums\OfferCategory;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_offers_index_lists_running_offers_only(): void
    {
        $running = Promotion::query()->create([
            'title_ar' => 'فحص شامل بخصم',
            'title_en' => 'Full checkup deal',
            'slug' => 'full-checkup-offer',
            'category' => OfferCategory::Checkup,
            'includes_ar' => 'CBC وكبد وكلى',
            'includes_en' => 'CBC, liver, kidney',
            'conditions_ar' => 'من سن 16',
            'original_price' => 1470,
            'offer_price' => 890,
            'discount_type' => 'percentage',
            'discount_value' => 39,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_featured' => true,
            'is_active' => true,
        ]);

        Promotion::query()->create([
            'title_ar' => 'عرض منتهي',
            'title_en' => 'Expired deal',
            'slug' => 'expired-deal',
            'category' => OfferCategory::Lab,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->get('/offers')
            ->assertOk()
            ->assertSee('فحص شامل بخصم')
            ->assertDontSee('عرض منتهي');

        $this->get('/offers/'.$running->slug)
            ->assertOk()
            ->assertSee('ماذا يشمل')
            ->assertSee('CBC وكبد وكلى');

        $this->assertSame(1, $running->fresh()->views_count);

        $this->get('/offers/expired-deal')->assertNotFound();
    }

    public function test_clinic_owner_creates_an_offer(): void
    {
        $this->actingAsClinicOwner();

        $this->post('/clinic/offers', [
            'title_ar' => 'تنظيف أسنان',
            'title_en' => 'Dental cleaning',
            'category' => OfferCategory::Dental->value,
            'includes_ar' => 'تنظيف وتلميع',
            'includes_en' => 'Cleaning and polish',
            'conditions_ar' => 'مرة واحدة',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'original_price' => 500,
            'offer_price' => 400,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addMonth()->toDateTimeString(),
            'is_active' => '1',
        ])->assertRedirect('/clinic/offers');

        $this->assertDatabaseHas('promotions', [
            'title_en' => 'Dental cleaning',
            'offer_price' => 400,
        ]);
    }
}
