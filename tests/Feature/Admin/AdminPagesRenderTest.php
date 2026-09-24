<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicSubscription;
use App\Models\DiscountCode;
use App\Models\Governorate;
use App\Models\InsuranceProvider;
use App\Models\Promotion;
use App\Models\SeoPage;
use App\Models\ServiceType;
use App\Models\SitePage;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SpecialtySeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every admin screen with real records so template errors surface in CI
 * rather than in the browser.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            GeographySeeder::class,
            SpecialtySeeder::class,
            ServiceTypeSeeder::class,
            SubscriptionPlanSeeder::class,
            PlatformSettingsSeeder::class,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($this->admin);
    }

    public function test_every_index_and_settings_page_renders(): void
    {
        $paths = [
            '/admin',
            '/admin/governorates',
            '/admin/cities',
            '/admin/specialties',
            '/admin/service-types',
            '/admin/insurance-providers',
            '/admin/plans',
            '/admin/discount-codes',
            '/admin/payments',
            '/admin/lab-tests',
            '/admin/lab-packages',
            '/admin/promotions',
            '/admin/articles',
            '/admin/site-pages',
            '/admin/seo-pages',
            '/admin/seo/site',
            '/admin/support',
            '/admin/staff',
            '/admin/notification-settings',
            '/admin/audit-logs',
            '/admin/users',
            '/admin/clinics',
            '/admin/doctors',
            '/admin/billing',
            '/admin/map',
            '/admin/attendance',
            '/admin/errors',
            '/admin/analytics',
            '/admin/reviews',
            '/admin/system',
            '/admin/loyalty',
            '/admin/bookings',
            '/admin/bookings/create',
            '/admin/lab-orders',
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk("Failed rendering {$path}");
        }
    }

    public function test_every_create_page_renders(): void
    {
        $paths = [
            '/admin/governorates/create',
            '/admin/cities/create',
            '/admin/specialties/create',
            '/admin/service-types/create',
            '/admin/insurance-providers/create',
            '/admin/plans/create',
            '/admin/discount-codes/create',
            '/admin/promotions/create',
            '/admin/lab-tests/create',
            '/admin/lab-packages/create',
            '/admin/articles/create',
            '/admin/site-pages/create',
            '/admin/staff/create',
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk("Failed rendering {$path}");
        }
    }

    public function test_every_edit_page_renders(): void
    {
        $promotion = Promotion::create([
            'title_ar' => 'خصم الافتتاح',
            'title_en' => 'Launch offer',
            'slug' => 'launch-offer',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $code = DiscountCode::create([
            'code' => 'LAUNCH25',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
        ]);

        $ticket = SupportTicket::create([
            'opened_by_user_id' => User::factory()->create()->id,
            'channel' => 'chat',
            'subject' => 'مشكلة في الحجز',
            'category' => 'booking',
        ]);

        $ticket->messages()->create([
            'sender_user_id' => $ticket->opened_by_user_id,
            'body' => 'لم أتلقَ تأكيدًا.',
            'sent_at' => now(),
        ]);

        $seoPage = SeoPage::create([
            'page_type' => 'city_specialty',
            'path' => '/nasr-city/dentistry',
            'city_id' => City::first()->id,
            'specialty_id' => Specialty::first()->id,
        ]);

        $provider = InsuranceProvider::factory()->create();
        $plan = SubscriptionPlan::first();
        $clinic = Clinic::factory()->create(['subscription_plan_id' => $plan->id]);
        $subscription = ClinicSubscription::factory()->create([
            'clinic_id' => $clinic->id,
            'subscription_plan_id' => $plan->id,
        ]);
        $sitePage = SitePage::factory()->create();

        $paths = [
            '/admin/governorates/'.Governorate::first()->id.'/edit',
            '/admin/cities/'.City::first()->id.'/edit',
            '/admin/specialties/'.Specialty::first()->id.'/edit',
            '/admin/insurance-providers/'.$provider->id.'/edit',
            '/admin/service-types/'.ServiceType::first()->id.'/edit',
            '/admin/plans/'.SubscriptionPlan::first()->id.'/edit',
            '/admin/discount-codes/'.$code->id.'/edit',
            '/admin/promotions/'.$promotion->id.'/edit',
            '/admin/seo-pages/'.$seoPage->id.'/edit',
            '/admin/site-pages/'.$sitePage->id.'/edit',
            '/admin/billing/'.$subscription->id,
            '/admin/staff/'.$this->admin->id.'/edit',
            '/admin/support/'.$ticket->id,
        ];

        foreach ($paths as $path) {
            $this->get($path)->assertOk("Failed rendering {$path}");
        }
    }

    public function test_admin_pages_render_in_english_too(): void
    {
        $this->withSession(['locale' => 'en']);

        $this->get('/admin')->assertOk()->assertSee('dir="ltr"', false);
        $this->get('/admin/plans/'.SubscriptionPlan::first()->id.'/edit')->assertOk();
        $this->get('/admin/notification-settings')->assertOk();
    }

    public function test_home_page_renders_rtl_by_default(): void
    {
        $this->get('/')->assertOk()->assertSee('dir="rtl"', false);
    }
}
