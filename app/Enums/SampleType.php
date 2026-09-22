<?php

namespace App\Enums;

enum SampleType: string
{
    case Blood = 'blood';
    case Urine = 'urine';
    case Stool = 'stool';
    case Swab = 'swab';
    case Other = 'other';

    public function labelAr(): string
    {
        return match ($this) {
            self::Blood => 'عينة دم',
            self::Urine => 'عينة بول',
            self::Stool => 'عينة براز',
            self::Swab => 'مسحة',
            self::Other => 'عينة أخرى',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Blood => 'Blood sample',
            self::Urine => 'Urine sample',
            self::Stool => 'Stool sample',
            self::Swab => 'Swab',
            self::Other => 'Other sample',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }
}
