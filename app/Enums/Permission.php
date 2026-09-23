<?php

namespace App\Enums;

enum Permission: string
{
    case ViewAdminPanel = 'view_admin_panel';
    case ManageGeography = 'manage_geography';
    case ManageSpecialties = 'manage_specialties';
    case ManageServiceTypes = 'manage_service_types';
    case ManageInsuranceProviders = 'manage_insurance_providers';
    case ManageSubscriptionPlans = 'manage_subscription_plans';
    case ManageDiscountCodes = 'manage_discount_codes';
    case ManagePaymentSettings = 'manage_payment_settings';
    case ManageStaff = 'manage_staff';
    case ManagePromotions = 'manage_promotions';
    case ManageLabCatalog = 'manage_lab_catalog';
    case ManageSeoPages = 'manage_seo_pages';
    case ManageSupportTickets = 'manage_support_tickets';
    case ModerateClinics = 'moderate_clinics';
    case ModerateReviews = 'moderate_reviews';
    case ManageUsers = 'manage_users';
    case ManageAttendance = 'manage_attendance';
    case ViewAnalytics = 'view_analytics';
    case OverseeBookings = 'oversee_bookings';
    case ViewAuditLog = 'view_audit_log';
    case ViewErrorReports = 'view_error_reports';

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
            self::ManageInsuranceProviders => 'إدارة شركات التأمين',
            self::ManageSubscriptionPlans => 'إدارة خطط الاشتراك',
            self::ManageDiscountCodes => 'إدارة أكواد الخصم',
            self::ManagePaymentSettings => 'إعدادات الدفع',
            self::ManageStaff => 'إدارة الموظفين',
            self::ManagePromotions => 'إدارة العروض',
            self::ManageLabCatalog => 'إدارة كتالوج التحاليل',
            self::ManageSeoPages => 'إدارة صفحات SEO',
            self::ManageSupportTickets => 'إدارة تذاكر الدعم',
            self::ModerateClinics => 'مراجعة العيادات',
            self::ModerateReviews => 'مراجعة التقييمات',
            self::ManageUsers => 'إدارة المستخدمين',
            self::ManageAttendance => 'الحضور والانصراف',
            self::ViewAnalytics => 'عرض التحليلات',
            self::OverseeBookings => 'متابعة حجوزات المنصة',
            self::ViewAuditLog => 'عرض سجل التدقيق',
            self::ViewErrorReports => 'تقارير الأخطاء',
            self::ManageOwnClinic => 'إدارة العيادة',
            self::ManageOwnClinicBilling => 'إدارة فواتير العيادة',
            self::ManageOwnSchedule => 'إدارة الجدول',
            self::ManageBookings => 'إدارة الحجوزات',
        };
    }
}
