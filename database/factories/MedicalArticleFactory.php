<?php

namespace Database\Factories;

use App\Enums\MedicalArticleCategory;
use App\Models\MedicalArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedicalArticle>
 */
class MedicalArticleFactory extends Factory
{
    public function definition(): array
    {
        $titleEn = fake()->sentence(4);

        return [
            'slug' => Str::slug($titleEn).'-'.fake()->unique()->numerify('###'),
            'title_ar' => 'دليل صحي',
            'title_en' => $titleEn,
            'excerpt_ar' => 'مقال تثقيفي من منصة حكيم.',
            'excerpt_en' => fake()->sentence(),
            'body_ar' => 'محتوى تثقيفي ولا يغني عن استشارة الطبيب.',
            'body_en' => fake()->paragraphs(2, true),
            'category' => MedicalArticleCategory::Prevention,
            'is_published' => true,
            'published_at' => now()->subDay(),
            'display_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
