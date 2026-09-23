<?php

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SitePage>
 */
class SitePageFactory extends Factory
{
    public function definition(): array
    {
        $headingEn = fake()->unique()->sentence(3);
        $slug = Str::slug($headingEn).'-'.fake()->unique()->numerify('###');

        return [
            'slug' => $slug,
            'path' => '/'.$slug,
            'is_system' => false,
            'is_published' => true,
            'heading_ar' => 'صفحة '.$slug,
            'heading_en' => $headingEn,
            'intro_ar' => 'مقدمة من لوحة التحكم.',
            'intro_en' => fake()->sentence(),
            'body_ar' => '<p>محتوى عربي من لوحة التحكم.</p>',
            'body_en' => '<p>'.fake()->paragraph().'</p>',
            'meta_title_ar' => 'عنوان ميتا '.$slug,
            'meta_title_en' => $headingEn,
            'meta_description_ar' => 'وصف ميتا للصفحة.',
            'meta_description_en' => fake()->sentence(),
            'sitemap_priority' => 5,
            'is_indexable' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
        ]);
    }
}
