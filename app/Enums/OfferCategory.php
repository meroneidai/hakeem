<?php

namespace App\Enums;

enum OfferCategory: string
{
    case Lab = 'lab';
    case Checkup = 'checkup';
    case Dental = 'dental';
    case Skin = 'skin';
    case Laser = 'laser';
    case Beauty = 'beauty';
    case PhysicalTherapy = 'physical_therapy';
    case Other = 'other';

    public function labelAr(): string
    {
        return match ($this) {
            self::Lab => 'تحاليل',
            self::Checkup => 'فحوصات شاملة',
            self::Dental => 'أسنان',
            self::Skin => 'جلدية',
            self::Laser => 'ليزر',
            self::Beauty => 'تجميل',
            self::PhysicalTherapy => 'علاج طبيعي',
            self::Other => 'أخرى',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Lab => 'Labs',
            self::Checkup => 'Checkups',
            self::Dental => 'Dental',
            self::Skin => 'Skin',
            self::Laser => 'Laser',
            self::Beauty => 'Beauty',
            self::PhysicalTherapy => 'Physiotherapy',
            self::Other => 'Other',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }
}
