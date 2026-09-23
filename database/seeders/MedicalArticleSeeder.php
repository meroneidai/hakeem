<?php

namespace Database\Seeders;

use App\Enums\MedicalArticleCategory;
use App\Models\MedicalArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MedicalArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title_ar' => 'متى تحتاج تحليل سكر صائم؟',
                'title_en' => 'When do you need a fasting blood sugar test?',
                'category' => MedicalArticleCategory::Tests,
                'excerpt_ar' => 'التحليل يوضح مستوى السكر بعد صيام، ويساعد الطبيب على تقييم الخطر قبل التشخيص.',
                'excerpt_en' => 'A fasting glucose test shows blood sugar after an overnight fast.',
                'body_ar' => "يُطلب تحليل السكر الصائم عادة بعد ساعات صيام يحددها المعمل.\nلا يغني عن زيارة الطبيب، ونتائجك تُفسَّر مع الأعراض والتاريخ المرضي.",
                'body_en' => "A fasting glucose test is usually taken after the lab's fasting window.\nIt does not replace a clinic visit; results are read with symptoms and history.",
            ],
            [
                'title_ar' => 'الحرارة عند الأطفال: متى تراجع الطبيب؟',
                'title_en' => 'Fever in children: when to see a doctor',
                'category' => MedicalArticleCategory::Children,
                'excerpt_ar' => 'ارتفاع الحرارة شائع. راقب التنفس، الشرب، والنشاط، واطلب كشفًا إذا ظهرت علامات خطر.',
                'excerpt_en' => 'Fever is common. Watch breathing, drinking and alertness, and book if warning signs appear.',
                'body_ar' => "الحرارة وحدها ليست تشخيصًا.\nاطلب كشف أطفال إذا كان الرضيع صغيرًا، أو إذا ظهر ضيق تنفس أو خمول شديد أو تشنج.",
                'body_en' => "Fever is a symptom, not a diagnosis.\nSee a pediatrician for a very young infant, breathing trouble, unusual drowsiness or a seizure.",
            ],
            [
                'title_ar' => 'العناية اليومية بالبشرة في الجو الحار',
                'title_en' => 'Daily skin care in hot weather',
                'category' => MedicalArticleCategory::Skin,
                'excerpt_ar' => 'الواقي الشمسي والترطيب يقللان التهيج. أي تغير مفاجئ في شامة يحتاج كشف جلدية.',
                'excerpt_en' => 'Sunscreen and moisturiser reduce irritation. A changing mole needs a dermatology visit.',
                'body_ar' => "اغسل برفق، رطّب بعد الاستحمام، واستخدم واقيًا شمسيًا في النهار.\nهذا دليل عام وليس خطة علاج لحالة جلدية.",
                'body_en' => "Wash gently, moisturise after bathing, and use daytime sunscreen.\nThis is general education, not a treatment plan.",
            ],
            [
                'title_ar' => 'كيف تستعد لكشف الأسنان',
                'title_en' => 'How to prepare for a dental visit',
                'category' => MedicalArticleCategory::Dental,
                'excerpt_ar' => 'اكتب الأدوية والحساسيات، ونظّف أسنانك كالمعتاد، واسأل عن الألم قبل الجلسة.',
                'excerpt_en' => 'List medicines and allergies, brush as usual, and mention pain before the visit.',
                'body_ar' => "أخبر طبيب الأسنان بالحمل والأدوية المزمنة.\nالتنظيف الدوري لا يغني عن علاج عصب إذا وُجد ألم مستمر.",
                'body_en' => "Tell the dentist about pregnancy and long-term medicines.\nA cleaning does not replace root-canal care if pain persists.",
            ],
            [
                'title_ar' => 'نصائح بسيطة لصحة نفسية يومية',
                'title_en' => 'Simple daily mental health habits',
                'category' => MedicalArticleCategory::MentalHealth,
                'excerpt_ar' => 'النوم المنتظم والحركة والدعم الاجتماعي تساعد. اطلب استشارة إذا استمر الحزن أو القلق.',
                'excerpt_en' => 'Sleep, movement and support help. Book a consultation if sadness or anxiety lasts.',
                'body_ar' => "يمكنك البدء بمشي قصير ونوم ثابت وتقليل الكافيين مساءً.\nإذا أثّر المزاج على العمل أو السلامة، احجز استشارة نفسية عبر حكيم.",
                'body_en' => "Start with a short walk, a stable sleep time, and less evening caffeine.\nIf mood affects work or safety, book a psychiatric consultation on Hakeem.",
            ],
        ];

        foreach ($articles as $index => $article) {
            MedicalArticle::query()->updateOrCreate(
                ['slug' => Str::slug($article['title_en'])],
                [
                    ...$article,
                    'is_published' => true,
                    'published_at' => now()->subDays(5 - $index),
                    'display_order' => $index + 1,
                ],
            );
        }
    }
}
