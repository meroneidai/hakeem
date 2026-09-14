<?php

namespace Tests\Feature\Admin;

use App\Enums\PlanFeature;
use App\Enums\RoleName;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(RoleName::PlatformAdmin);
    }

    public function test_plan_is_created_with_only_the_selected_features_enabled(): void
    {
        $this->post('/admin/plans', [
            'name_ar' => 'النمو',
            'name_en' => 'Growth',
            'monthly_price' => 750,
            'yearly_discount_pct' => 15,
            'booking_cap' => 1500,
            'is_active' => '1',
            'features' => [
                PlanFeature::ServiceClinicAppointment->value => '1',
                PlanFeature::BasicAnalytics->value => '1',
            ],
        ])->assertRedirect('/admin/plans');

        $plan = SubscriptionPlan::with('featureFlags')->firstWhere('slug', 'growth');

        $this->assertTrue($plan->allows(PlanFeature::ServiceClinicAppointment));
        $this->assertTrue($plan->allows(PlanFeature::BasicAnalytics));
        $this->assertFalse($plan->allows(PlanFeature::AdvancedAnalytics));

        // Every feature gets a row so the admin UI always renders a full matrix.
        $this->assertCount(count(PlanFeature::cases()), $plan->featureFlags);
    }

    public function test_yearly_price_falls_back_to_the_discounted_monthly_total(): void
    {
        $plan = SubscriptionPlan::create([
            'name_ar' => 'النمو',
            'name_en' => 'Growth',
            'slug' => 'growth',
            'monthly_price' => 1000,
            'yearly_price' => 0,
            'yearly_discount_pct' => 20,
        ]);

        $this->assertSame(9600.0, $plan->effectiveYearlyPrice());
    }

    public function test_only_one_plan_can_be_the_default_free_tier(): void
    {
        $starter = SubscriptionPlan::create([
            'name_ar' => 'الأساسية', 'name_en' => 'Starter', 'slug' => 'starter',
            'monthly_price' => 0, 'is_default_free' => true,
        ]);

        $this->post('/admin/plans', [
            'name_ar' => 'المجانية الجديدة',
            'name_en' => 'New Free',
            'monthly_price' => 0,
            'is_default_free' => '1',
        ])->assertRedirect();

        $this->assertFalse($starter->fresh()->is_default_free);
        $this->assertTrue(SubscriptionPlan::firstWhere('slug', 'new-free')->is_default_free);
    }

    public function test_the_default_free_plan_cannot_be_deleted(): void
    {
        $plan = SubscriptionPlan::create([
            'name_ar' => 'الأساسية', 'name_en' => 'Starter', 'slug' => 'starter',
            'monthly_price' => 0, 'is_default_free' => true,
        ]);

        $this->from('/admin/plans')->delete('/admin/plans/'.$plan->id)->assertSessionHas('error');

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    public function test_null_caps_mean_unlimited(): void
    {
        $this->post('/admin/plans', [
            'name_ar' => 'الاحترافية',
            'name_en' => 'Pro',
            'monthly_price' => 1500,
        ])->assertRedirect();

        $plan = SubscriptionPlan::firstWhere('slug', 'pro');

        $this->assertNull($plan->booking_cap);
        $this->assertTrue($plan->hasUnlimitedBookings());
    }
}
