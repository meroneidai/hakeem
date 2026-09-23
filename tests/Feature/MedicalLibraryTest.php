<?php

namespace Tests\Feature;

use App\Enums\MedicalArticleCategory;
use App\Enums\RoleName;
use App\Models\MedicalArticle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_is_listed_and_shown(): void
    {
        $article = MedicalArticle::factory()->create([
            'title_ar' => 'دليل السكر الصائم',
            'slug' => 'fasting-glucose',
            'category' => MedicalArticleCategory::Tests,
        ]);
        MedicalArticle::factory()->draft()->create([
            'title_ar' => 'مسودة مخفية',
            'slug' => 'hidden-draft',
        ]);

        $this->get('/medical-library')
            ->assertOk()
            ->assertSee('دليل السكر الصائم')
            ->assertDontSee('مسودة مخفية');

        $this->get('/medical-library/fasting-glucose')
            ->assertOk()
            ->assertSee($article->title_ar);

        $this->get('/medical-library/hidden-draft')->assertNotFound();
    }

    public function test_platform_admin_can_publish_an_article(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $this->get('/admin/articles/create')->assertOk();

        $this->post('/admin/articles', [
            'title_ar' => 'الوقاية من الإنفلونزا',
            'title_en' => 'Flu prevention',
            'body_ar' => 'اغسل يديك واطلب كشفًا إذا ساء التنفس.',
            'body_en' => 'Wash your hands and book a visit if breathing worsens.',
            'category' => MedicalArticleCategory::Prevention->value,
            'is_published' => '1',
        ])->assertRedirect('/admin/articles');

        $this->assertDatabaseHas('medical_articles', [
            'title_en' => 'Flu prevention',
            'is_published' => true,
        ]);

        $this->get('/medical-library/flu-prevention')
            ->assertOk()
            ->assertSee('الوقاية من الإنفلونزا');
    }

    public function test_article_html_is_rendered_and_scripts_are_stripped(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::PlatformAdmin);
        $this->actingAs($admin);

        $article = MedicalArticle::factory()->create([
            'title_ar' => 'مقال منسّق',
            'title_en' => 'Formatted article',
            'slug' => 'formatted-article',
        ]);

        $this->from('/admin/articles/'.$article->id.'/edit')
            ->put('/admin/articles/'.$article->id, [
                'title_ar' => 'مقال منسّق',
                'title_en' => 'Formatted article',
                'body_ar' => '<h2>عنوان</h2><p><b>نص عريض</b><script>alert(1)</script></p>',
                'body_en' => '<h2>Heading</h2><p><b>Bold</b></p>',
                'category' => MedicalArticleCategory::Prevention->value,
                'is_published' => '1',
            ])
            ->assertRedirect('/admin/articles');

        $article->refresh();
        $this->assertStringNotContainsString('<script>', $article->body_ar);
        $this->assertStringNotContainsString('alert(1)', $article->body_ar);

        $this->get('/medical-library/formatted-article')
            ->assertOk()
            ->assertSee('عنوان', false)
            ->assertSee('<b>نص عريض</b>', false);
    }
}
