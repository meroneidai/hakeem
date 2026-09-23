<?php

namespace App\Enums;

enum MedicalArticleCategory: string
{
    case Prevention = 'prevention';
    case Symptoms = 'symptoms';
    case Tests = 'tests';
    case Children = 'children';
    case Nutrition = 'nutrition';
    case MentalHealth = 'mental_health';
    case Dental = 'dental';
    case Skin = 'skin';

    public function labelAr(): string
    {
        return match ($this) {
            self::Prevention => 'وقاية',
            self::Symptoms => 'أعراض',
            self::Tests => 'تحاليل',
            self::Children => 'أطفال',
            self::Nutrition => 'تغذية',
            self::MentalHealth => 'صحة نفسية',
            self::Dental => 'أسنان',
            self::Skin => 'جلدية',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Prevention => 'Prevention',
            self::Symptoms => 'Symptoms',
            self::Tests => 'Tests',
            self::Children => 'Children',
            self::Nutrition => 'Nutrition',
            self::MentalHealth => 'Mental health',
            self::Dental => 'Dental',
            self::Skin => 'Skin',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }
}
