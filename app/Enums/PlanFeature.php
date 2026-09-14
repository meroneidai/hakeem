<?php

namespace App\Enums;

/**
 * Feature flags toggled per subscription plan from the admin dashboard.
 * Plan contents are data-driven — never hardcode plan capabilities against a plan slug.
 */
enum PlanFeature: string
{
    case ServiceClinicAppointment = 'service.clinic_appointment';
    case ServiceHomeVisit = 'service.home_visit';
    case ServiceVideoConsultation = 'service.video_consultation';
    case ServiceLabTest = 'service.lab_test';
    case ServiceHomeLabTest = 'service.home_lab_test';
    case ServicePsychiatricConsultation = 'service.psychiatric_consultation';
    case ServiceCatalogItems = 'service.catalog_items';

    case MultipleAddresses = 'clinic.multiple_addresses';
    case MultipleDoctors = 'clinic.multiple_doctors';
    case ReceptionRole = 'clinic.reception_role';
    case ClinicPromotions = 'clinic.promotions';
    case BasicAnalytics = 'analytics.basic';
    case AdvancedAnalytics = 'analytics.advanced';
    case PromotedPlacement = 'growth.promoted_placement';
    case WhatsappAgent = 'growth.whatsapp_agent';
    case PrescriptionBranding = 'records.prescription_branding';

    public function labelAr(): string
    {
        return match ($this) {
            self::ServiceClinicAppointment => 'حجز موعد بالعيادة',
            self::ServiceHomeVisit => 'زيارة منزلية',
            self::ServiceVideoConsultation => 'استشارة بالفيديو',
            self::ServiceLabTest => 'تحاليل بالمعمل',
            self::ServiceHomeLabTest => 'تحاليل منزلية',
            self::ServicePsychiatricConsultation => 'استشارة نفسية أونلاين',
            self::ServiceCatalogItems => 'كتالوج الخدمات المفصّل',
            self::MultipleAddresses => 'فروع متعددة',
            self::MultipleDoctors => 'أطباء متعددون',
            self::ReceptionRole => 'دور الاستقبال',
            self::ClinicPromotions => 'عروض العيادة',
            self::BasicAnalytics => 'تحليلات أساسية',
            self::AdvancedAnalytics => 'تحليلات متقدمة',
            self::PromotedPlacement => 'ظهور مميز',
            self::WhatsappAgent => 'الحجز عبر واتساب',
            self::PrescriptionBranding => 'روشتات موثّقة بهوية العيادة',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::ServiceClinicAppointment => 'Clinic appointments',
            self::ServiceHomeVisit => 'Home visits',
            self::ServiceVideoConsultation => 'Video consultations',
            self::ServiceLabTest => 'In-clinic lab tests',
            self::ServiceHomeLabTest => 'Home lab tests',
            self::ServicePsychiatricConsultation => 'Online psychiatric consultations',
            self::ServiceCatalogItems => 'Itemised service catalog',
            self::MultipleAddresses => 'Multiple branches',
            self::MultipleDoctors => 'Multiple doctors',
            self::ReceptionRole => 'Reception role',
            self::ClinicPromotions => 'Clinic promotions',
            self::BasicAnalytics => 'Basic analytics',
            self::AdvancedAnalytics => 'Advanced analytics',
            self::PromotedPlacement => 'Promoted placement',
            self::WhatsappAgent => 'WhatsApp booking agent',
            self::PrescriptionBranding => 'Branded prescriptions',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }

    public function group(): string
    {
        return str_contains($this->value, '.')
            ? str($this->value)->before('.')->toString()
            : 'general';
    }

    public static function forServiceType(ServiceTypeCode|string $code): ?self
    {
        $value = $code instanceof ServiceTypeCode ? $code->value : $code;

        return self::tryFrom('service.'.$value);
    }
}
