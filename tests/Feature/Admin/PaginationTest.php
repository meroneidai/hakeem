<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Governorate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(RoleName::PlatformAdmin);

        // Governorates paginate at 20, so 25 rows guarantees a second page.
        foreach (range(1, 25) as $i) {
            Governorate::create([
                'name_ar' => 'محافظة '.$i,
                'name_en' => 'Governorate '.$i,
                'slug' => 'governorate-'.$i,
            ]);
        }
    }

    public function test_pagination_labels_are_translated_into_arabic(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/admin/governorates');

        $response->assertOk()
            ->assertSee('السابق')
            ->assertSee('التالي')
            ->assertSee('عرض 1 إلى 20 من إجمالي 25')
            ->assertSee('الانتقال إلى صفحة 2');
    }

    public function test_pagination_labels_are_translated_into_english(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get('/admin/governorates');

        $response->assertOk()
            ->assertSee('Previous')
            ->assertSee('Next')
            ->assertSee('Showing 1 to 20 of 25')
            ->assertSee('Go to page 2');
    }

    public function test_pagination_never_leaks_raw_html_entities_or_default_english_strings(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/admin/governorates');

        $response->assertDontSee('&raquo;', false)
            ->assertDontSee('&laquo;', false)
            ->assertDontSee('Pagination Navigation')
            ->assertDontSee('Showing', false);
    }

    public function test_arrow_direction_follows_the_active_locale(): void
    {
        // Right-pointing chevron leads back to page 1 in Arabic, and forward in English.
        $rightChevron = 'M8.25 4.5l7.5 7.5-7.5 7.5';

        $arabic = $this->withSession(['locale' => 'ar'])->get('/admin/governorates')->getContent();
        $english = $this->withSession(['locale' => 'en'])->get('/admin/governorates')->getContent();

        $this->assertStringContainsString(
            $rightChevron,
            $this->paginationMarkup($arabic, 'السابق'),
            'Arabic “previous” should use a right-pointing chevron.'
        );

        $this->assertStringContainsString(
            $rightChevron,
            $this->paginationMarkup($english, 'Next'),
            'English “next” should use a right-pointing chevron.'
        );
    }

    /**
     * Returns the markup of the pagination control carrying the given aria-label.
     */
    private function paginationMarkup(string $html, string $ariaLabel): string
    {
        $start = strpos($html, 'aria-label="'.$ariaLabel.'"');

        $this->assertNotFalse($start, "No pagination control labelled “{$ariaLabel}” was rendered.");

        return substr($html, $start, 400);
    }
}
