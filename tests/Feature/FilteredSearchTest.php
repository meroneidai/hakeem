<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\PageView;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilteredSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_query_with_specialty_and_city_filters_still_lists_doctors(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/search?q=&type=doctors&specialty='.$provider['specialty']->slug.'&governorate='.$provider['governorate']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee((string) number_format((float) $provider['doctor']->consultation_fee))
            ->assertSee(__('discover.doctors.fee'))
            ->assertSee(__('reviews.empty'));
    }

    public function test_services_search_lists_matching_doctors_with_primary_filters(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/search?q=&type=services&specialty='.$provider['specialty']->slug.'&city='.$provider['city']->slug.'&gender=female')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee($provider['clinic']->name_ar)
            ->assertSee('name="q"', false)
            ->assertSee('name="specialty"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="gender"', false)
            ->assertSee(__('discover.view_profile'))
            ->assertSee(__('discover.book_now'))
            ->assertDontSee('<x-input', false);
    }

    public function test_json_search_includes_fee_years_and_empty_rating(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/search?q=&type=doctors&specialty='.$provider['specialty']->slug)
            ->assertOk()
            ->assertJsonPath('doctors.0.name', $provider['doctor']->name)
            ->assertJsonPath('doctors.0.years', 12)
            ->assertJsonPath('doctors.0.fee', 350)
            ->assertJsonPath('doctors.0.rating_count', 0);
    }

    public function test_json_services_search_returns_doctors_with_clinic_and_visit_counts(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/search?q=&type=services&specialty='.$provider['specialty']->slug.'&gender=female')
            ->assertOk()
            ->assertJsonPath('doctors.0.name', $provider['doctor']->name)
            ->assertJsonPath('doctors.0.clinics.0', $provider['clinic']->name)
            ->assertJsonPath('doctors.0.visits', 0)
            ->assertJsonPath('type', 'services');
    }

    public function test_status_endpoint_reports_integration_readiness_without_secrets(): void
    {
        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['integrations' => ['mail', 'firebase', 'sms', 'whatsapp']]);
    }

    public function test_guest_can_open_the_complaints_form(): void
    {
        $this->get('/complaints')->assertOk()->assertSee(__('pages.complaints.heading'));
    }

    public function test_complaint_creates_a_support_ticket(): void
    {
        $this->seedRoles();

        $this->from('/complaints')->post('/complaints', [
            'name' => 'مريض شكوى',
            'phone' => '01099998888',
            'subject' => 'انتظار طويل',
            'body' => 'انتظرت أكثر من ساعة بعد الموعد.',
        ])->assertRedirect('/complaints')->assertSessionHas('status');

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'انتظار طويل',
            'category' => 'complaint',
        ]);
    }

    public function test_admin_system_settings_store_encrypted_keys(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/system')->assertOk()->assertSee(__('admin.integrations.heading'));

        $this->put('/admin/system', [
            'integrations' => [
                'mail_host' => 'smtp.example.test',
                'mail_port' => 587,
                'firebase_project_id' => 'hakeem-test',
                'firebase_web_api_key' => 'web-key',
                'sms_key' => 'sms-secret',
            ],
        ])->assertRedirect();

        $settings = app(Settings::class);
        $this->assertSame('smtp.example.test', $settings->get('integrations.mail_host'));
        $this->assertSame('sms-secret', $settings->get('integrations.sms_key'));
        $this->assertTrue(Setting::query()->where('key', 'integrations.sms_key')->value('is_encrypted'));
    }

    public function test_support_agent_can_open_analytics(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/analytics')->assertOk()->assertSee(__('admin.analytics.heading'));
    }

    public function test_homepage_records_a_hashed_page_view(): void
    {
        $this->seedListableProvider();

        $this->get('/')->assertOk();

        $this->assertDatabaseCount('page_views', 1);
        $this->assertNotSame('127.0.0.1', PageView::query()->value('ip_hash'));
    }

    public function test_multi_word_search_requires_every_token(): void
    {
        $match = $this->seedListableProvider([
            'clinic_name_ar' => 'عيادة النور التخصصية',
            'clinic_name_en' => 'Al Noor Specialty',
        ]);

        $other = Clinic::factory()->verified()->create([
            'subscription_plan_id' => $match['plan']->id,
            'name_ar' => 'عيادة الشفاء',
            'name_en' => 'Al Shifa Clinic',
        ]);
        ClinicAddress::factory()->create([
            'clinic_id' => $other->id,
            'city_id' => $match['city']->id,
            'latitude' => 30.05,
            'longitude' => 31.24,
        ]);

        $this->get('/clinics?'.http_build_query(['q' => 'النور التخصصية']))
            ->assertOk()
            ->assertSee('عيادة النور التخصصية')
            ->assertDontSee('عيادة الشفاء');
    }
}
