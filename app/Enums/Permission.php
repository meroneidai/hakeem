<?php

namespace App\Enums;

enum Permission: string
{
    case ViewAdminPanel = 'view_admin_panel';
    case ManageGeography = 'manage_geography';
    case ManageSpecialties = 'manage_specialties';
    case ManageServiceTypes = 'manage_service_types';
    case ManageSubscriptionPlans = 'manage_subscription_plans';
    case ManageDiscountCodes = 'manage_discount_codes';
    case ManagePaymentSettings = 'manage_payment_settings';
    case ManageStaff = 'manage_staff';
    case ManagePromotions = 'manage_promotions';
    case ManageSeoPages = 'manage_seo_pages';
    case ManageSupportTickets = 'manage_support_tickets';
    case ModerateClinics = 'moderate_clinics';
    case ModerateReviews = 'moderate_reviews';
    case ViewAnalytics = 'view_analytics';
    case ViewAuditLog = 'view_audit_log';

    // Clinic-side permissions (used from Phase 2 onward).
    case ManageOwnClinic = 'manage_own_clinic';
    case ManageOwnClinicBilling = 'manage_own_clinic_billing';
    case ManageOwnSchedule = 'manage_own_schedule';
    case ManageBookings = 'manage_bookings';

    public function labelAr(): string
    {
        return match ($this) {
            self::ViewAdminPanel => 'الوصول إلى لوحة التحكم',
            self::ManageGeography => 'إدارة المحافظات والمدن',
            self::ManageSpecialties => 'إدارة التخصصات',
            self::ManageServiceTypes => 'إدارة أنواع الخدمات',
            self::ManageSubscriptionPlans => 'إدارة خطط الاشتراك',
            self::ManageDiscountCodes => 'إدارة أكواد الخصم',
            self::ManagePaymentSettings => 'إعدادات الدفع',
            self::ManageStaff => 'إدارة الموظفين',
            self::ManagePromotions => 'إدارة العروض',
            self::ManageSeoPages => 'إدارة صفحات SEO',
            self::ManageSupportTickets => 'إدارة تذاكر الدعم',
            self::ModerateClinics => 'مراجعة العيادات',
            self::ModerateReviews => 'مراجعة التقييمات',
            self::ViewAnalytics => 'عرض التحليلات',
            self::ViewAuditLog => 'عرض سجل التدقيق',
            self::ManageOwnClinic => 'إدارة العيادة',
            self::ManageOwnClinicBilling => 'إدارة فواتير العيادة',
            self::ManageOwnSchedule => 'إدارة الجدول',
            self::ManageBookings => 'إدارة الحجوزات',
        };
    }
}
