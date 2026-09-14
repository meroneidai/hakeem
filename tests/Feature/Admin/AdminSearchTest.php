<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin list search must stay case-insensitive on every driver. Plain `LIKE` is
 * case-insensitive on SQLite but case-sensitive on PostgreSQL, so these cover the
 * gap that only shows up against the production engine.
 */
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(RoleName::PlatformAdmin);
    }

    public function test_governorate_search_ignores_case(): void
    {
        Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);
        Governorate::create(['name_ar' => 'الجيزة', 'name_en' => 'Giza', 'slug' => 'giza']);

        foreach (['cairo', 'CAIRO', 'CaIrO'] as $term) {
            $this->get('/admin/governorates?q='.$term)
                ->assertOk()
                ->assertSee('Cairo')
                ->assertDontSee('Giza');
        }
    }

    public function test_city_search_ignores_case(): void
    {
        $governorate = Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);

        City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => 'المعادي', 'name_en' => 'Maadi', 'slug' => 'maadi',
        ]);
        City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City', 'slug' => 'nasr-city',
        ]);

        $this->get('/admin/cities?q=MAADI')
            ->assertOk()
            ->assertSee('Maadi')
            ->assertDontSee('Nasr City');
    }

    public function test_specialty_search_ignores_case(): void
    {
        Specialty::create(['name_ar' => 'الأسنان', 'name_en' => 'Dentistry', 'slug' => 'dentistry']);
        Specialty::create(['name_ar' => 'الجلدية', 'name_en' => 'Dermatology', 'slug' => 'dermatology']);

        $this->get('/admin/specialties?q=DENTIST')
            ->assertOk()
            ->assertSee('Dentistry')
            ->assertDontSee('Dermatology');
    }

    public function test_arabic_search_still_matches(): void
    {
        Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);
        Governorate::create(['name_ar' => 'الجيزة', 'name_en' => 'Giza', 'slug' => 'giza']);

        $this->get('/admin/governorates?q='.urlencode('القاهرة'))
            ->assertOk()
            ->assertSee('Cairo')
            ->assertDontSee('Giza');
    }
}
