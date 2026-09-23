<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\SitePage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SitePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePageCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_updates_a_system_page_and_public_site_shows_it_with_seo(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(SitePageSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $page = SitePage::query()->where('slug', 'about')->first();
        $this->assertNotNull($page);

        $this->get('/admin/site-pages')->assertOk()->assertSee(__('admin.site_pages.heading'));
        $this->get('/admin/site-pages/'.$page->id.'/edit')->assertOk();

        $this->from('/admin/site-pages/'.$page->id.'/edit')
            ->put('/admin/site-pages/'.$page->id, [
                'heading_ar' => 'عن منصة حكيم المحدّثة',
                'heading_en' => 'About updated Hakeem',
                'intro_ar' => 'مقدمة جديدة من لوحة التحكم.',
                'intro_en' => 'New intro from the dashboard.',
                'body_ar' => '<h2>المهمة المحدّثة</h2><p>نص عربي جديد.</p>',
                'body_en' => '<h2>Updated mission</h2><p>New English copy.</p>',
                'meta_title_ar' => 'ميتا عن حكيم',
                'meta_title_en' => 'About Hakeem meta',
                'meta_description_ar' => 'وصف ميتا من لوحة التحكم عن حكيم.',
                'meta_description_en' => 'Meta description from the dashboard.',
                'sitemap_priority' => 8,
                'is_published' => '1',
                'is_indexable' => '1',
            ])
            ->assertRedirect('/admin/site-pages');

        $this->get('/about')
            ->assertOk()
            ->assertSee('عن منصة حكيم المحدّثة')
            ->assertSee('المهمة المحدّثة')
            ->assertSee('وصف ميتا من لوحة التحكم عن حكيم.', false)
            ->assertSee('ميتا عن حكيم', false);
    }

    public function test_admin_creates_a_custom_page_that_appears_publicly_and_in_the_sitemap(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $this->get('/admin/site-pages/create')->assertOk();

        $this->post('/admin/site-pages', [
            'heading_ar' => 'شركاء حكيم',
            'heading_en' => 'Hakeem partners',
            'slug' => 'partners',
            'intro_ar' => 'انضم كشريك للمنصة.',
            'intro_en' => 'Join as a partner.',
            'body_ar' => '<p>نرحب بالشركاء في كل المحافظات.</p>',
            'body_en' => '<p>We welcome partners in every governorate.</p>',
            'meta_title_ar' => 'شركاء حكيم | شراكات',
            'meta_title_en' => 'Hakeem partners',
            'meta_description_ar' => 'صفحة الشركاء الرسمية لحكيم.',
            'meta_description_en' => 'Official Hakeem partners page.',
            'sitemap_priority' => 6,
            'is_published' => '1',
            'is_indexable' => '1',
        ])->assertRedirect('/admin/site-pages');

        $this->assertDatabaseHas('site_pages', [
            'slug' => 'partners',
            'path' => '/partners',
            'is_system' => false,
        ]);

        $this->get('/partners')
            ->assertOk()
            ->assertSee('شركاء حكيم')
            ->assertSee('نرحب بالشركاء في كل المحافظات.')
            ->assertSee('صفحة الشركاء الرسمية لحكيم.', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/partners'), false);
    }

    public function test_unpublished_custom_page_is_not_public(): void
    {
        SitePage::factory()->draft()->create([
            'slug' => 'draft-page',
            'path' => '/draft-page',
            'heading_ar' => 'مسودة مخفية',
        ]);

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $this->from('/admin/site-pages/create')
            ->post('/admin/site-pages', [
                'heading_ar' => 'تصادم',
                'heading_en' => 'Collision',
                'slug' => 'doctors',
                'sitemap_priority' => 5,
                'is_published' => '1',
                'is_indexable' => '1',
            ])
            ->assertRedirect('/admin/site-pages/create')
            ->assertSessionHasErrors('slug');
    }

    public function test_support_agent_cannot_manage_site_pages(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/site-pages')->assertForbidden();
    }

    public function test_script_tags_are_stripped_from_saved_body(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $this->post('/admin/site-pages', [
            'heading_ar' => 'آمنة',
            'heading_en' => 'Safe page',
            'slug' => 'safe-page',
            'body_ar' => '<p>مرحبا</p><script>alert(1)</script>',
            'body_en' => '<p>Hello</p><script>alert(1)</script>',
            'sitemap_priority' => 5,
            'is_published' => '1',
            'is_indexable' => '1',
        ])->assertRedirect('/admin/site-pages');

        $page = SitePage::query()->where('slug', 'safe-page')->first();

        $this->assertNotNull($page);
        $this->assertStringNotContainsString('<script>', $page->body_ar);
        $this->assertStringNotContainsString('alert(1)', $page->body_ar);
        $this->assertStringContainsString('<p>مرحبا</p>', $page->body_ar);
    }
}
