<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\PageView;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_lists_real_page_views(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame(1, PageView::query()->count());
        $this->assertSame('/', PageView::query()->value('path'));

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/analytics')
            ->assertOk()
            ->assertSee(__('admin.analytics.top_paths'))
            ->assertSee('/');
    }

    public function test_saved_google_ids_are_injected_on_public_pages(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->put('/admin/analytics', [
            'analytics' => [
                'ga_measurement_id' => 'G-ABC123XYZ',
                'gtm_container_id' => 'GTM-TEST001',
                'search_console_verification' => 'gsc_token_abc',
            ],
        ])->assertRedirect();

        $this->assertSame('G-ABC123XYZ', app(Settings::class)->get('analytics.ga_measurement_id'));

        $this->get('/')
            ->assertOk()
            ->assertSee('G-ABC123XYZ', false)
            ->assertSee('GTM-TEST001', false)
            ->assertSee('google-site-verification', false)
            ->assertSee('gsc_token_abc', false);
    }

    public function test_invalid_measurement_id_is_rejected(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->from('/admin/analytics')
            ->put('/admin/analytics', [
                'analytics' => [
                    'ga_measurement_id' => 'not-a-ga-id',
                ],
            ])
            ->assertRedirect('/admin/analytics')
            ->assertSessionHasErrors('analytics.ga_measurement_id');
    }

    public function test_support_agent_cannot_update_tracking_ids(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->put('/admin/analytics', [
            'analytics' => [
                'ga_measurement_id' => 'G-ABC123XYZ',
            ],
        ])->assertForbidden();
    }
}
