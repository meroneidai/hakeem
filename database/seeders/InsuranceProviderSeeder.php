<?php

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsuranceProviderSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $order => [$nameAr, $nameEn, $slug, $hotline]) {
            InsuranceProvider::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_ar' => $nameAr,
                    'name_en' => $nameEn,
                    'hotline' => $hotline,
                    'is_active' => true,
                    'display_order' => $order + 1,
                ],
            );
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: ?string}>
     */
    private function data(): array
    {
        return [
            ['مصر للتأمين', 'Misr Insurance', 'misr-insurance', '19806'],
            ['أكسا للتأمين', 'AXA Egypt', 'axa-egypt', '19390'],
            ['متلايف', 'MetLife Egypt', 'metlife-egypt', '19020'],
            ['بوبا إيجيبت', 'Bupa Egypt', 'bupa-egypt', '16816'],
            ['نكست كير', 'NextCare', 'nextcare', '16812'],
            ['ميد رايت', 'MedRight', 'medright', '16177'],
            ['جلوب ميد', 'GlobeMed Egypt', 'globemed-egypt', '16344'],
            ['التأمين الصحي الشامل', 'Universal Health Insurance', 'universal-health-insurance', null],
        ];
    }
}
