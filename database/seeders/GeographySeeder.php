<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

/**
 * Egypt's 27 governorates and their main cities/districts (md_files/01 §3).
 * Slugs are stable because they appear in SEO landing-page URLs.
 */
class GeographySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $order => [$nameAr, $nameEn, $slug, $cities]) {
            $governorate = Governorate::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'display_order' => $order + 1, 'is_active' => true],
            );

            foreach ($cities as $cityOrder => [$cityAr, $cityEn, $citySlug]) {
                City::updateOrCreate(
                    ['slug' => $citySlug],
                    [
                        'governorate_id' => $governorate->id,
                        'name_ar' => $cityAr,
                        'name_en' => $cityEn,
                        'display_order' => $cityOrder + 1,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: list<array{0: string, 1: string, 2: string}>}>
     */
    private function data(): array
    {
        return [
            ['القاهرة', 'Cairo', 'cairo', [
                ['مدينة نصر', 'Nasr City', 'nasr-city'],
                ['مصر الجديدة', 'Heliopolis', 'heliopolis'],
                ['المعادي', 'Maadi', 'maadi'],
                ['الزمالك', 'Zamalek', 'zamalek'],
                ['وسط البلد', 'Downtown Cairo', 'downtown-cairo'],
                ['القاهرة الجديدة', 'New Cairo', 'new-cairo'],
                ['المقطم', 'Mokattam', 'mokattam'],
                ['حلوان', 'Helwan', 'helwan'],
                ['عين شمس', 'Ain Shams', 'ain-shams'],
                ['شبرا', 'Shubra', 'shubra'],
                ['المرج', 'El Marg', 'el-marg'],
                ['الشروق', 'El Shorouk', 'el-shorouk'],
            ]],
            ['الجيزة', 'Giza', 'giza', [
                ['الدقي', 'Dokki', 'dokki'],
                ['المهندسين', 'Mohandessin', 'mohandessin'],
                ['الهرم', 'Haram', 'haram'],
                ['فيصل', 'Faisal', 'faisal'],
                ['السادس من أكتوبر', '6th of October', 'sixth-of-october'],
                ['الشيخ زايد', 'Sheikh Zayed', 'sheikh-zayed'],
                ['إمبابة', 'Imbaba', 'imbaba'],
                ['العجوزة', 'Agouza', 'agouza'],
                ['البدرشين', 'Badrashin', 'badrashin'],
                ['العياط', 'Ayat', 'ayat'],
            ]],
            ['الإسكندرية', 'Alexandria', 'alexandria', [
                ['سموحة', 'Smouha', 'smouha'],
                ['سيدي جابر', 'Sidi Gaber', 'sidi-gaber'],
                ['المنتزه', 'Montazah', 'montazah'],
                ['العجمي', 'Agami', 'agami'],
                ['برج العرب', 'Borg El Arab', 'borg-el-arab'],
                ['محرم بك', 'Moharram Bek', 'moharram-bek'],
                ['المندرة', 'Mandara', 'mandara'],
                ['العصافرة', 'Asafra', 'asafra'],
                ['الورديان', 'Wardian', 'wardian'],
            ]],
            ['القليوبية', 'Qalyubia', 'qalyubia', [
                ['بنها', 'Benha', 'benha'],
                ['شبرا الخيمة', 'Shubra El Kheima', 'shubra-el-kheima'],
                ['قليوب', 'Qalyub', 'qalyub'],
                ['القناطر الخيرية', 'Qanater El Khayreya', 'qanater-el-khayreya'],
                ['طوخ', 'Toukh', 'toukh'],
                ['كفر شكر', 'Kafr Shukr', 'kafr-shukr'],
                ['العبور', 'Obour', 'obour'],
            ]],
            ['بورسعيد', 'Port Said', 'port-said', [
                ['الشرق', 'El Sharq', 'port-said-el-sharq'],
                ['العرب', 'El Arab', 'port-said-el-arab'],
                ['المناخ', 'El Manakh', 'el-manakh'],
                ['بورفؤاد', 'Port Fouad', 'port-fouad'],
            ]],
            ['السويس', 'Suez', 'suez', [
                ['الأربعين', 'El Arbaeen', 'el-arbaeen'],
                ['فيصل - السويس', 'Faisal Suez', 'faisal-suez'],
                ['الجناين', 'El Ganayen', 'el-ganayen'],
                ['عتاقة', 'Ataqa', 'ataqa'],
            ]],
            ['دمياط', 'Damietta', 'damietta', [
                ['دمياط', 'Damietta City', 'damietta-city'],
                ['رأس البر', 'Ras El Bar', 'ras-el-bar'],
                ['فارسكور', 'Faraskur', 'faraskur'],
                ['كفر سعد', 'Kafr Saad', 'kafr-saad'],
                ['دمياط الجديدة', 'New Damietta', 'new-damietta'],
            ]],
            ['الدقهلية', 'Dakahlia', 'dakahlia', [
                ['المنصورة', 'Mansoura', 'mansoura'],
                ['طلخا', 'Talkha', 'talkha'],
                ['ميت غمر', 'Mit Ghamr', 'mit-ghamr'],
                ['السنبلاوين', 'Sinbillawin', 'sinbillawin'],
                ['بلقاس', 'Belqas', 'belqas'],
                ['المطرية', 'El Matareya', 'el-matareya'],
                ['أجا', 'Aga', 'aga'],
            ]],
            ['الشرقية', 'Sharqia', 'sharqia', [
                ['الزقازيق', 'Zagazig', 'zagazig'],
                ['بلبيس', 'Bilbeis', 'bilbeis'],
                ['العاشر من رمضان', '10th of Ramadan', 'tenth-of-ramadan'],
                ['أبو حماد', 'Abu Hammad', 'abu-hammad'],
                ['فاقوس', 'Faqous', 'faqous'],
                ['منيا القمح', 'Minya El Qamh', 'minya-el-qamh'],
                ['كفر صقر', 'Kafr Saqr', 'kafr-saqr'],
            ]],
            ['كفر الشيخ', 'Kafr El Sheikh', 'kafr-el-sheikh', [
                ['كفر الشيخ', 'Kafr El Sheikh City', 'kafr-el-sheikh-city'],
                ['دسوق', 'Desouk', 'desouk'],
                ['بيلا', 'Bila', 'bila'],
                ['فوه', 'Fuwwah', 'fuwwah'],
                ['مطوبس', 'Metoubes', 'metoubes'],
                ['سيدي سالم', 'Sidi Salem', 'sidi-salem'],
            ]],
            ['الغربية', 'Gharbia', 'gharbia', [
                ['طنطا', 'Tanta', 'tanta'],
                ['المحلة الكبرى', 'El Mahalla El Kubra', 'el-mahalla-el-kubra'],
                ['كفر الزيات', 'Kafr El Zayat', 'kafr-el-zayat'],
                ['زفتى', 'Zefta', 'zefta'],
                ['السنطة', 'El Santa', 'el-santa'],
                ['بسيون', 'Basyoun', 'basyoun'],
            ]],
            ['المنوفية', 'Monufia', 'monufia', [
                ['شبين الكوم', 'Shebin El Kom', 'shebin-el-kom'],
                ['منوف', 'Menouf', 'menouf'],
                ['أشمون', 'Ashmoun', 'ashmoun'],
                ['السادات', 'Sadat City', 'sadat-city'],
                ['قويسنا', 'Quweisna', 'quweisna'],
                ['تلا', 'Tala', 'tala'],
            ]],
            ['البحيرة', 'Beheira', 'beheira', [
                ['دمنهور', 'Damanhour', 'damanhour'],
                ['كفر الدوار', 'Kafr El Dawwar', 'kafr-el-dawwar'],
                ['رشيد', 'Rosetta', 'rosetta'],
                ['إيتاي البارود', 'Itay El Barud', 'itay-el-barud'],
                ['أبو المطامير', 'Abu El Matamir', 'abu-el-matamir'],
                ['وادي النطرون', 'Wadi El Natrun', 'wadi-el-natrun'],
            ]],
            ['الإسماعيلية', 'Ismailia', 'ismailia', [
                ['الإسماعيلية', 'Ismailia City', 'ismailia-city'],
                ['فايد', 'Fayed', 'fayed'],
                ['القنطرة شرق', 'Qantara East', 'qantara-east'],
                ['أبو صوير', 'Abu Suwayr', 'abu-suwayr'],
                ['التل الكبير', 'El Tal El Kebir', 'el-tal-el-kebir'],
            ]],
            ['بني سويف', 'Beni Suef', 'beni-suef', [
                ['بني سويف', 'Beni Suef City', 'beni-suef-city'],
                ['الواسطى', 'El Wasta', 'el-wasta'],
                ['ببا', 'Biba', 'biba'],
                ['الفشن', 'El Fashn', 'el-fashn'],
                ['ناصر', 'Nasser', 'nasser-beni-suef'],
            ]],
            ['الفيوم', 'Faiyum', 'faiyum', [
                ['الفيوم', 'Faiyum City', 'faiyum-city'],
                ['سنورس', 'Sinnuris', 'sinnuris'],
                ['طامية', 'Tamiya', 'tamiya'],
                ['إطسا', 'Itsa', 'itsa'],
                ['أبشواي', 'Ibshaway', 'ibshaway'],
            ]],
            ['المنيا', 'Minya', 'minya', [
                ['المنيا', 'Minya City', 'minya-city'],
                ['ملوي', 'Mallawi', 'mallawi'],
                ['بني مزار', 'Beni Mazar', 'beni-mazar'],
                ['مطاي', 'Matay', 'matay'],
                ['سمالوط', 'Samalut', 'samalut'],
                ['أبو قرقاص', 'Abu Qurqas', 'abu-qurqas'],
            ]],
            ['أسيوط', 'Asyut', 'asyut', [
                ['أسيوط', 'Asyut City', 'asyut-city'],
                ['ديروط', 'Dairut', 'dairut'],
                ['منفلوط', 'Manfalut', 'manfalut'],
                ['أبنوب', 'Abnub', 'abnub'],
                ['القوصية', 'El Qusiya', 'el-qusiya'],
                ['أبو تيج', 'Abu Tig', 'abu-tig'],
            ]],
            ['سوهاج', 'Sohag', 'sohag', [
                ['سوهاج', 'Sohag City', 'sohag-city'],
                ['أخميم', 'Akhmim', 'akhmim'],
                ['جرجا', 'Girga', 'girga'],
                ['طهطا', 'Tahta', 'tahta'],
                ['المراغة', 'El Maragha', 'el-maragha'],
                ['البلينا', 'El Balyana', 'el-balyana'],
            ]],
            ['قنا', 'Qena', 'qena', [
                ['قنا', 'Qena City', 'qena-city'],
                ['نجع حمادي', 'Nag Hammadi', 'nag-hammadi'],
                ['قفط', 'Qift', 'qift'],
                ['أبو تشت', 'Abu Tesht', 'abu-tesht'],
                ['دشنا', 'Deshna', 'deshna'],
            ]],
            ['أسوان', 'Aswan', 'aswan', [
                ['أسوان', 'Aswan City', 'aswan-city'],
                ['كوم أمبو', 'Kom Ombo', 'kom-ombo'],
                ['إدفو', 'Edfu', 'edfu'],
                ['دراو', 'Daraw', 'daraw'],
                ['أبو سمبل', 'Abu Simbel', 'abu-simbel'],
            ]],
            ['الأقصر', 'Luxor', 'luxor', [
                ['الأقصر', 'Luxor City', 'luxor-city'],
                ['إسنا', 'Esna', 'esna'],
                ['أرمنت', 'Armant', 'armant'],
                ['القرنة', 'El Qurna', 'el-qurna'],
            ]],
            ['البحر الأحمر', 'Red Sea', 'red-sea', [
                ['الغردقة', 'Hurghada', 'hurghada'],
                ['سفاجا', 'Safaga', 'safaga'],
                ['القصير', 'El Quseir', 'el-quseir'],
                ['مرسى علم', 'Marsa Alam', 'marsa-alam'],
                ['رأس غارب', 'Ras Gharib', 'ras-gharib'],
            ]],
            ['الوادي الجديد', 'New Valley', 'new-valley', [
                ['الخارجة', 'El Kharga', 'el-kharga'],
                ['الداخلة', 'El Dakhla', 'el-dakhla'],
                ['الفرافرة', 'El Farafra', 'el-farafra'],
                ['باريس', 'Paris Oasis', 'paris-oasis'],
            ]],
            ['مطروح', 'Matrouh', 'matrouh', [
                ['مرسى مطروح', 'Marsa Matrouh', 'marsa-matrouh'],
                ['العلمين', 'El Alamein', 'el-alamein'],
                ['الحمام', 'El Hammam', 'el-hammam'],
                ['سيوة', 'Siwa', 'siwa'],
                ['الضبعة', 'El Dabaa', 'el-dabaa'],
            ]],
            ['شمال سيناء', 'North Sinai', 'north-sinai', [
                ['العريش', 'Arish', 'arish'],
                ['بئر العبد', 'Bir El Abd', 'bir-el-abd'],
                ['الشيخ زويد', 'Sheikh Zuweid', 'sheikh-zuweid'],
                ['نخل', 'Nakhl', 'nakhl'],
            ]],
            ['جنوب سيناء', 'South Sinai', 'south-sinai', [
                ['شرم الشيخ', 'Sharm El Sheikh', 'sharm-el-sheikh'],
                ['دهب', 'Dahab', 'dahab'],
                ['نويبع', 'Nuweiba', 'nuweiba'],
                ['طور سيناء', 'El Tor', 'el-tor'],
                ['سانت كاترين', 'Saint Catherine', 'saint-catherine'],
            ]],
        ];
    }
}
