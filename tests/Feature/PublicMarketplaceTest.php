<?php

namespace Tests\Feature;

use App\Enums\ServiceTypeCode;
use App\Models\Clinic;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_and_directories_render_listable_providers(): void
    {
        $provider = $this->seedListableProvider();
        $pending = Clinic::factory()->create([
            'subscription_plan_id' => $provider['plan']->id,
            'name_ar' => 'عيادة غير موثقة',
            'name_en' => 'Unverified Clinic',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee($provider['clinic']->name_ar)
            ->assertDontSee($pending->name_ar);

        $this->get('/doctors/'.$provider['doctor']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar);

        $this->get('/clinics')
            ->assertOk()
            ->assertSee($provider['clinic']->name_ar)
            ->assertDontSee($pending->name_ar);

        $this->get('/clinics/'.$provider['clinic']->slug)
            ->assertOk()
            ->assertSee($provider['clinic']->name_ar);

        $this->get('/clinics/'.$pending->slug)->assertNotFound();
    }

    public function test_inactive_doctor_profile_is_not_found(): void
    {
        $provider = $this->seedListableProvider(['doctor_active' => false]);

        $this->get('/doctors/'.$provider['doctor']->slug)->assertNotFound();
    }

    public function test_specialty_service_and_city_pages_use_prefixed_urls(): void
    {
        $provider = $this->seedListableProvider();
        ServiceType::query()->create([
            'code' => ServiceTypeCode::HomeVisit->value,
            'name_ar' => 'زيارة منزلية',
            'name_en' => 'Home Visit',
            'slug' => 'home-visit',
            'is_active' => true,
        ]);
        ServiceType::query()->create([
            'code' => ServiceTypeCode::VideoConsultation->value,
            'name_ar' => 'استشارة بالفيديو',
            'name_en' => 'Video Consultation',
            'slug' => 'video-consultation',
            'is_active' => true,
        ]);

        $this->get('/specialties')
            ->assertOk()
            ->assertSee($provider['specialty']->name_ar);

        $this->get('/specialties/'.$provider['specialty']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar);

        $this->get('/services')
            ->assertOk()
            ->assertSee($provider['serviceType']->name_ar);

        $this->get('/services/'.$provider['serviceType']->slug)->assertOk();
        $this->get('/home-care')->assertOk()->assertSee('/services/home-visit', false);
        $this->get('/teleconsultation')->assertOk()->assertSee('/services/video-consultation', false);

        $this->get('/cities')
            ->assertOk()
            ->assertSee($provider['city']->name_ar);

        $this->get('/cities/'.$provider['city']->slug)
            ->assertOk()
            ->assertSee($provider['clinic']->name_ar);

        $this->get('/specialties/'.$provider['specialty']->slug.'/'.$provider['city']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar);

        $this->get('/services/'.$provider['serviceType']->slug.'/'.$provider['city']->slug)
            ->assertOk()
            ->assertSee(__('discover.services_page.in_place', [
                'service' => $provider['serviceType']->name,
                'place' => $provider['city']->name,
            ]));

        $this->get('/accessibility')->assertOk()->assertSee(__('pages.accessibility.heading'));
        $this->get('/help/booking')->assertOk()->assertSee(__('pages.help.items.booking.title'));
        $this->get('/medical-library')->assertOk();
    }

    public function test_search_is_noindex_and_finds_doctors(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/search')
            ->assertOk()
            ->assertSee('noindex,nofollow');

        $this->get('/search?q=سارة')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar);
    }

    public function test_static_and_contact_pages_render(): void
    {
        $this->get('/how-it-works')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/help')->assertOk();
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/cookies')->assertOk();
        $this->get('/cancellation-policy')->assertOk();
        $this->get('/medical-disclaimer')->assertOk();
        $this->get('/accessibility')->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/login')->assertOk()->assertSee('noindex,nofollow');
    }
}
