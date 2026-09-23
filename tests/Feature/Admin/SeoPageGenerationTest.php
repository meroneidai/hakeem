<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\City;
use App\Models\Governorate;
use App\Models\SeoPage;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Services\SeoPageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPageGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $governorate = Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);
        City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City', 'slug' => 'nasr-city',
        ]);
        Specialty::create(['name_ar' => 'أسنان', 'name_en' => 'Dentistry', 'slug' => 'dentistry', 'category' => 'dental']);
        ServiceType::create([
            'code' => 'home_visit', 'name_ar' => 'زيارة منزلية', 'name_en' => 'Home Visit', 'slug' => 'home-visit',
        ]);
    }

    public function test_it_generates_one_page_per_page_type(): void
    {
        app(SeoPageGenerator::class)->generateMissing();

        $this->assertDatabaseHas('seo_pages', ['path' => '/cities/cairo', 'page_type' => 'governorate']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/cities/nasr-city', 'page_type' => 'city']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/specialties/dentistry', 'page_type' => 'specialty']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/services/home-visit', 'page_type' => 'service']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/specialties/dentistry/cairo', 'page_type' => 'governorate_specialty']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/specialties/dentistry/nasr-city', 'page_type' => 'city_specialty']);
    }

    /**
     * Regression: identity is the page type plus its relations, not the path, so a
     * second run must not create fallback-path duplicates.
     */
    public function test_generation_is_idempotent(): void
    {
        $generator = app(SeoPageGenerator::class);

        $first = $generator->generateMissing();
        $second = $generator->generateMissing();

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
        $this->assertSame($first, SeoPage::count());
    }

    public function test_new_reference_data_adds_only_the_missing_pages(): void
    {
        $generator = app(SeoPageGenerator::class);
        $generator->generateMissing();
        $before = SeoPage::count();

        Specialty::create(['name_ar' => 'عيون', 'name_en' => 'Ophthalmology', 'slug' => 'ophthalmology']);

        // 1 specialty page + 1 governorate×specialty + 1 city×specialty.
        $this->assertSame(3, $generator->generateMissing());
        $this->assertSame($before + 3, SeoPage::count());
    }

    public function test_the_city_page_falls_back_when_its_slug_clashes_with_a_governorate(): void
    {
        City::create([
            'governorate_id' => Governorate::first()->id,
            'name_ar' => 'القاهرة', 'name_en' => 'Cairo City', 'slug' => 'cairo-city',
        ]);
        Governorate::create(['name_ar' => 'الأقصر', 'name_en' => 'Luxor', 'slug' => 'luxor']);
        City::create([
            'governorate_id' => Governorate::firstWhere('slug', 'luxor')->id,
            'name_ar' => 'الأقصر', 'name_en' => 'Luxor', 'slug' => 'luxor-city',
        ]);

        app(SeoPageGenerator::class)->generateMissing();

        $this->assertDatabaseHas('seo_pages', ['path' => '/cities/luxor', 'page_type' => 'governorate']);
        $this->assertDatabaseHas('seo_pages', ['path' => '/cities/luxor-city', 'page_type' => 'city']);
    }

    public function test_admin_can_trigger_generation_and_override_meta(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/seo-pages/generate')->assertRedirect();

        $page = SeoPage::firstWhere('path', '/specialties/dentistry/nasr-city');

        $this->put('/admin/seo-pages/'.$page->id, [
            'meta_title_ar' => 'أفضل أطباء الأسنان في مدينة نصر',
            'meta_title_en' => 'Best dentists in Nasr City',
            'sitemap_priority' => 9,
            'is_indexable' => '1',
        ])->assertRedirect('/admin/seo-pages');

        $page->refresh();

        $this->assertSame('أفضل أطباء الأسنان في مدينة نصر', $page->meta_title_ar);
        $this->assertSame(9, $page->sitemap_priority);
    }
}
