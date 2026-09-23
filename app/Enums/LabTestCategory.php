<?php

namespace App\Enums;

enum LabTestCategory: string
{
    case Blood = 'blood';
    case Liver = 'liver';
    case Kidney = 'kidney';
    case Thyroid = 'thyroid';
    case Hormones = 'hormones';
    case Vitamins = 'vitamins';
    case Diabetes = 'diabetes';
    case Lipids = 'lipids';
    case Iron = 'iron';
    case Urine = 'urine';
    case Heart = 'heart';
    case Infection = 'infection';
    case Allergy = 'allergy';
    case TumorMarkers = 'tumor_markers';

    public function labelAr(): string
    {
        return match ($this) {
            self::Blood => 'دم',
            self::Liver => 'وظائف الكبد',
            self::Kidney => 'وظائف الكلى',
            self::Thyroid => 'الغدة الدرقية',
            self::Hormones => 'هرمونات',
            self::Vitamins => 'فيتامينات',
            self::Diabetes => 'سكر',
            self::Lipids => 'دهون',
            self::Iron => 'حديد',
            self::Urine => 'بول',
            self::Heart => 'قلب',
            self::Infection => 'عدوى ومناعة',
            self::Allergy => 'حساسية',
            self::TumorMarkers => 'دلالات أورام',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Blood => 'Blood',
            self::Liver => 'Liver',
            self::Kidney => 'Kidney',
            self::Thyroid => 'Thyroid',
            self::Hormones => 'Hormones',
            self::Vitamins => 'Vitamins',
            self::Diabetes => 'Diabetes',
            self::Lipids => 'Lipids',
            self::Iron => 'Iron',
            self::Urine => 'Urine',
            self::Heart => 'Heart',
            self::Infection => 'Infection',
            self::Allergy => 'Allergy',
            self::TumorMarkers => 'Tumor markers',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }
}
