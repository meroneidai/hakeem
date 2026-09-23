<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\ClinicService;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_includes_canonical_open_graph_and_json_ld(): void
    {
        $this->seedListableProvider();

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"MedicalOrganization"', false)
            ->assertSee('"@type":"WebPage"', false)
            ->assertSee(__('discover.app.heading'))
            ->assertSee(__('agent.title'))
            ->assertSee(__('discover.dock.whatsapp'))
            ->assertSee('x-data="headerSearch', false);
    }

    public function test_doctor_profile_includes_physician_schema(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/doctors/'.$provider['doctor']->slug)
            ->assertOk()
            ->assertSee('"@type":"Physician"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_sitemap_and_robots_are_published(): void
    {
        $provider = $this->seedListableProvider();
        ClinicService::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'service_type_id' => $provider['serviceType']->id,
            'price' => 250,
            'is_active' => true,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(url('/'), false)
            ->assertSee(route('doctors.show', $provider['doctor']), false)
            ->assertSee(route('doctors.index'), false)
            ->assertSee(route('about'), false)
            ->assertSee(route('library.index'), false)
            ->assertSee(route('specialties.index'), false)
            ->assertSee(route('specialties.location', [$provider['specialty'], $provider['city']->slug]), false)
            ->assertSee(route('services.location', [$provider['serviceType'], $provider['city']->slug]), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /search')
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_platform_admin_can_update_site_seo_defaults(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $this->get('/admin/seo/site')->assertOk();

        $this->put('/admin/seo/site', [
            'seo' => [
                'site_title_ar' => 'حكيم',
                'site_title_en' => 'Hakeem',
                'default_description_ar' => 'وصف افتراضي للتجربة',
                'default_description_en' => 'Default description for tests',
                'keywords_ar' => 'حكيم, حجز طبيب, عيادات',
                'keywords_en' => 'Hakeem, book doctor, clinics',
                'og_image' => 'https://example.test/og.png',
                'twitter_site' => '@hakeem',
                'app_ios_url' => 'https://apps.apple.com/app/hakeem',
                'app_android_url' => 'https://play.google.com/store/apps/details?id=eg.hakeem',
            ],
            'general' => [
                'support_whatsapp' => '01000000000',
                'support_phone' => '01000000001',
                'support_email' => 'hello@hakeem.test',
            ],
            'social' => [
                'facebook' => 'https://www.facebook.com/hakeem',
                'instagram' => 'https://www.instagram.com/hakeem',
            ],
        ])->assertRedirect();

        $settings = app(Settings::class);
        $this->assertSame('وصف افتراضي للتجربة', $settings->get('seo.default_description_ar'));
        $this->assertSame('حكيم, حجز طبيب, عيادات', $settings->get('seo.keywords_ar'));
        $this->assertSame('حكيم', $settings->get('seo.site_title_ar'));
        $this->assertSame('https://example.test/og.png', $settings->get('seo.og_image'));
        $this->assertSame('01000000000', $settings->get('general.support_whatsapp'));
        $this->assertSame('01000000001', $settings->get('general.support_phone'));
        $this->assertSame('https://www.facebook.com/hakeem', $settings->get('social.facebook'));
    }

    public function test_public_pages_include_fixed_title_keywords_contacts_and_schema(): void
    {
        $this->seedListableProvider();
        $settings = app(Settings::class);
        $settings->setMany([
            'seo.site_title_ar' => 'حكيم',
            'seo.keywords_ar' => 'حكيم, حجز طبيب, عيادات',
            'seo.default_description_ar' => 'وصف افتراضي للتجربة',
            'seo.twitter_site' => '@hakeem',
        ], 'seo');
        $settings->setMany([
            'general.support_phone' => '01000000001',
            'general.support_email' => 'hello@hakeem.test',
            'general.support_whatsapp' => '01000000000',
        ], 'general');
        $settings->set('social.facebook', 'https://www.facebook.com/hakeem', 'social');

        $this->get('/')
            ->assertOk()
            ->assertSee('حكيم, حجز طبيب, عيادات', false)
            ->assertSee('name="keywords"', false)
            ->assertSee('"@type":"ContactPoint"', false)
            ->assertSee('hello@hakeem.test', false)
            ->assertSee('+201000000001', false)
            ->assertSee('https://www.facebook.com/hakeem', false)
            ->assertSee('https://x.com/hakeem', false)
            ->assertSee('favicon.svg', false);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('"@type":"ContactPage"', false)
            ->assertSee('hello@hakeem.test', false)
            ->assertSee('+201000000001', false);
    }

    public function test_header_search_lives_only_on_the_homepage_hero(): void
    {
        $this->seedListableProvider();

        $this->get('/doctors')
            ->assertOk()
            ->assertDontSee('x-data="headerSearch', false)
            ->assertSee(__('discover.dock.call'))
            ->assertSee('"@type":"MedicalOrganization"', false)
            ->assertSee('"@type":"WebPage"', false);
    }
}
