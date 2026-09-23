<?php

namespace Tests\Feature;

use App\Enums\OfferCategory;
use App\Models\Promotion;
use App\Support\Deeplink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_cards_include_book_now_and_a_mobile_share_link(): void
    {
        $offer = Promotion::query()->create([
            'title_ar' => 'فحص شامل بخصم',
            'title_en' => 'Full checkup deal',
            'slug' => 'full-checkup-offer',
            'category' => OfferCategory::Checkup,
            'includes_ar' => 'CBC وكبد وكلى',
            'includes_en' => 'CBC, liver, kidney',
            'discount_type' => 'percentage',
            'discount_value' => 39,
            'original_price' => 1470,
            'offer_price' => 890,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_featured' => true,
            'is_active' => true,
        ]);

        $share = Deeplink::forRoute('offers.show', $offer);

        $this->get('/offers')
            ->assertOk()
            ->assertSee(__('discover.book_now'))
            ->assertSee(__('discover.share'))
            ->assertSee('data-share-url="'.$share['web'].'"', false)
            ->assertSee('data-app-url="'.$share['app'].'"', false)
            ->assertSee('hakeem://offers/full-checkup-offer', false);
    }

    public function test_clinic_cards_include_book_now_and_a_mobile_share_link(): void
    {
        $provider = $this->seedListableProvider();
        $share = Deeplink::forRoute('clinics.show', $provider['clinic']);

        $this->get('/clinics')
            ->assertOk()
            ->assertSee(__('discover.book_now'))
            ->assertSee(__('discover.share'))
            ->assertSee(route('book.doctors.create', $provider['doctor']), false)
            ->assertSee('data-share-url="'.$share['web'].'"', false)
            ->assertSee('hakeem://clinics/'.$provider['clinic']->slug, false);
    }
}
