<?php

namespace Tests\Feature;

use App\Enums\OfferApprovalStatus;
use App\Enums\OfferCategory;
use App\Enums\RoleName;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_offer_stays_hidden_until_admin_approves(): void
    {
        $this->actingAsClinicOwner();

        $this->post('/clinic/offers', [
            'title_ar' => 'تنظيف أسنان',
            'title_en' => 'Dental cleaning',
            'category' => OfferCategory::Dental->value,
            'includes_ar' => 'تنظيف وتلميع',
            'includes_en' => 'Cleaning and polish',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'original_price' => 500,
            'offer_price' => 400,
            'starts_at' => now()->subHour()->toDateTimeString(),
            'ends_at' => now()->addMonth()->toDateTimeString(),
        ])->assertRedirect('/clinic/offers');

        $offer = Promotion::query()->where('title_en', 'Dental cleaning')->firstOrFail();

        $this->assertSame(OfferApprovalStatus::Pending, $offer->approval_status);
        $this->assertFalse($offer->is_active);

        $this->get('/offers')->assertOk()->assertDontSee('تنظيف أسنان');
        $this->get('/offers/'.$offer->slug)->assertNotFound();

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/promotions/'.$offer->id.'/approve')
            ->assertRedirect()
            ->assertSessionHas('status');

        $offer->refresh();
        $this->assertSame(OfferApprovalStatus::Approved, $offer->approval_status);
        $this->assertTrue($offer->is_active);

        $this->get('/offers')->assertOk()->assertSee('تنظيف أسنان');
    }

    public function test_admin_can_reject_a_pending_offer(): void
    {
        $this->seedRoles();

        $offer = Promotion::query()->create([
            'title_ar' => 'عرض مرفوض',
            'title_en' => 'Rejected deal',
            'slug' => 'rejected-deal',
            'category' => OfferCategory::Lab,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => false,
            'approval_status' => OfferApprovalStatus::Pending,
        ]);

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/promotions/'.$offer->id.'/reject', [
            'rejection_reason' => 'Incomplete details',
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame(OfferApprovalStatus::Rejected, $offer->approval_status);
        $this->assertFalse($offer->is_active);
        $this->assertSame('Incomplete details', $offer->rejection_reason);
    }
}
