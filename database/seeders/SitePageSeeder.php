<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

class SitePageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $data) {
            SitePage::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'path' => SitePage::pathForSlug($slug),
                    'is_system' => true,
                    'is_published' => true,
                    'is_indexable' => true,
                    ...$data,
                ],
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pages(): array
    {
        $helpAr = '';
        $helpEn = '';

        foreach (trans('pages.help.items', [], 'ar') as $item) {
            $helpAr .= '<h2>'.e($item['title']).'</h2><p>'.e($item['body']).'</p>';
        }

        foreach (trans('pages.help.items', [], 'en') as $item) {
            $helpEn .= '<h2>'.e($item['title']).'</h2><p>'.e($item['body']).'</p>';
        }

        $howAr = '<ol>';
        $howEn = '<ol>';

        foreach (trans('pages.how.steps', [], 'ar') as $step) {
            $howAr .= '<li>'.e($step).'</li>';
        }

        foreach (trans('pages.how.steps', [], 'en') as $step) {
            $howEn .= '<li>'.e($step).'</li>';
        }

        $howAr .= '</ol>';
        $howEn .= '</ol>';

        return [
            'home' => [
                'heading_ar' => trans('discover.hero_title', [], 'ar'),
                'heading_en' => trans('discover.hero_title', [], 'en'),
                'intro_ar' => trans('discover.hero_subtitle', [], 'ar'),
                'intro_en' => trans('discover.hero_subtitle', [], 'en'),
                'meta_title_ar' => trans('discover.hero_title', [], 'ar'),
                'meta_title_en' => trans('discover.hero_title', [], 'en'),
                'meta_description_ar' => trans('discover.hero_subtitle', [], 'ar'),
                'meta_description_en' => trans('discover.hero_subtitle', [], 'en'),
                'sitemap_priority' => 10,
            ],
            'about' => [
                'heading_ar' => trans('pages.about.heading', [], 'ar'),
                'heading_en' => trans('pages.about.heading', [], 'en'),
                'intro_ar' => trans('pages.about.lead', [], 'ar'),
                'intro_en' => trans('pages.about.lead', [], 'en'),
                'body_ar' => '<h2>'.e(trans('pages.about.mission', [], 'ar')).'</h2><p>'.e(trans('pages.about.mission_body', [], 'ar')).'</p><h2>'.e(trans('pages.about.vision', [], 'ar')).'</h2><p>'.e(trans('pages.about.vision_body', [], 'ar')).'</p>',
                'body_en' => '<h2>'.e(trans('pages.about.mission', [], 'en')).'</h2><p>'.e(trans('pages.about.mission_body', [], 'en')).'</p><h2>'.e(trans('pages.about.vision', [], 'en')).'</h2><p>'.e(trans('pages.about.vision_body', [], 'en')).'</p>',
                'meta_title_ar' => trans('pages.about.heading', [], 'ar'),
                'meta_title_en' => trans('pages.about.heading', [], 'en'),
                'meta_description_ar' => trans('pages.about.lead', [], 'ar'),
                'meta_description_en' => trans('pages.about.lead', [], 'en'),
                'sitemap_priority' => 5,
            ],
            'how-it-works' => [
                'heading_ar' => trans('pages.how.heading', [], 'ar'),
                'heading_en' => trans('pages.how.heading', [], 'en'),
                'intro_ar' => trans('pages.how.lead', [], 'ar'),
                'intro_en' => trans('pages.how.lead', [], 'en'),
                'body_ar' => $howAr,
                'body_en' => $howEn,
                'meta_title_ar' => trans('pages.how.heading', [], 'ar'),
                'meta_title_en' => trans('pages.how.heading', [], 'en'),
                'meta_description_ar' => trans('pages.how.lead', [], 'ar'),
                'meta_description_en' => trans('pages.how.lead', [], 'en'),
                'sitemap_priority' => 5,
            ],
            'help' => [
                'heading_ar' => trans('pages.help.heading', [], 'ar'),
                'heading_en' => trans('pages.help.heading', [], 'en'),
                'intro_ar' => trans('pages.help.lead', [], 'ar'),
                'intro_en' => trans('pages.help.lead', [], 'en'),
                'body_ar' => $helpAr,
                'body_en' => $helpEn,
                'meta_title_ar' => trans('pages.help.heading', [], 'ar'),
                'meta_title_en' => trans('pages.help.heading', [], 'en'),
                'meta_description_ar' => trans('pages.help.lead', [], 'ar'),
                'meta_description_en' => trans('pages.help.lead', [], 'en'),
                'sitemap_priority' => 4,
            ],
            'terms' => $this->legal('terms', 3),
            'privacy' => $this->legal('privacy', 3),
            'cookies' => $this->legal('cookies', 3),
            'cancellation' => $this->legal('cancellation', 3),
            'disclaimer' => $this->legal('disclaimer', 3),
            'accessibility' => $this->legal('accessibility', 3),
            'contact' => [
                'heading_ar' => trans('pages.contact.heading', [], 'ar'),
                'heading_en' => trans('pages.contact.heading', [], 'en'),
                'intro_ar' => trans('pages.contact.lead', [], 'ar'),
                'intro_en' => trans('pages.contact.lead', [], 'en'),
                'meta_title_ar' => trans('pages.contact.heading', [], 'ar'),
                'meta_title_en' => trans('pages.contact.heading', [], 'en'),
                'meta_description_ar' => trans('pages.contact.lead', [], 'ar'),
                'meta_description_en' => trans('pages.contact.lead', [], 'en'),
                'sitemap_priority' => 5,
            ],
            'complaints' => [
                'heading_ar' => trans('pages.complaints.heading', [], 'ar'),
                'heading_en' => trans('pages.complaints.heading', [], 'en'),
                'intro_ar' => trans('pages.complaints.lead', [], 'ar'),
                'intro_en' => trans('pages.complaints.lead', [], 'en'),
                'meta_title_ar' => trans('pages.complaints.heading', [], 'ar'),
                'meta_title_en' => trans('pages.complaints.heading', [], 'en'),
                'meta_description_ar' => trans('pages.complaints.lead', [], 'ar'),
                'meta_description_en' => trans('pages.complaints.lead', [], 'en'),
                'sitemap_priority' => 4,
            ],
            'home-care' => [
                'heading_ar' => trans('pages.home_care.heading', [], 'ar'),
                'heading_en' => trans('pages.home_care.heading', [], 'en'),
                'intro_ar' => trans('pages.home_care.lead', [], 'ar'),
                'intro_en' => trans('pages.home_care.lead', [], 'en'),
                'meta_title_ar' => trans('pages.home_care.heading', [], 'ar'),
                'meta_title_en' => trans('pages.home_care.heading', [], 'en'),
                'meta_description_ar' => trans('pages.home_care.lead', [], 'ar'),
                'meta_description_en' => trans('pages.home_care.lead', [], 'en'),
                'sitemap_priority' => 6,
            ],
            'teleconsultation' => [
                'heading_ar' => trans('pages.teleconsultation.heading', [], 'ar'),
                'heading_en' => trans('pages.teleconsultation.heading', [], 'en'),
                'intro_ar' => trans('pages.teleconsultation.lead', [], 'ar'),
                'intro_en' => trans('pages.teleconsultation.lead', [], 'en'),
                'meta_title_ar' => trans('pages.teleconsultation.heading', [], 'ar'),
                'meta_title_en' => trans('pages.teleconsultation.heading', [], 'en'),
                'meta_description_ar' => trans('pages.teleconsultation.lead', [], 'ar'),
                'meta_description_en' => trans('pages.teleconsultation.lead', [], 'en'),
                'sitemap_priority' => 6,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legal(string $key, int $priority): array
    {
        $headingAr = trans('pages.'.$key.'.heading', [], 'ar');
        $headingEn = trans('pages.'.$key.'.heading', [], 'en');
        $bodyAr = trans('pages.'.$key.'.body', [], 'ar');
        $bodyEn = trans('pages.'.$key.'.body', [], 'en');

        return [
            'heading_ar' => $headingAr,
            'heading_en' => $headingEn,
            'intro_ar' => null,
            'intro_en' => null,
            'body_ar' => '<p>'.e($bodyAr).'</p>',
            'body_en' => '<p>'.e($bodyEn).'</p>',
            'meta_title_ar' => $headingAr,
            'meta_title_en' => $headingEn,
            'meta_description_ar' => $bodyAr,
            'meta_description_en' => $bodyEn,
            'sitemap_priority' => $priority,
        ];
    }
}
