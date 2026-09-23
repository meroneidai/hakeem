<?php

namespace App\Enums;

enum RoleName: string
{
    case PlatformAdmin = 'platform_admin';
    case SupportAgent = 'support_agent';
    case ClinicOwner = 'clinic_owner';
    case Doctor = 'doctor';
    case Reception = 'reception';
    case Patient = 'patient';

    public function labelAr(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'مدير المنصة',
            self::SupportAgent => 'موظف الدعم',
            self::ClinicOwner => 'مالك العيادة',
            self::Doctor => 'طبيب',
            self::Reception => 'استقبال',
            self::Patient => 'مريض',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Platform Admin',
            self::SupportAgent => 'Support Agent',
            self::ClinicOwner => 'Clinic Owner',
            self::Doctor => 'Doctor',
            self::Reception => 'Reception',
            self::Patient => 'Patient',
        };
    }

    /**
     * Roles that may sign in to the /admin area.
     */
    public static function internalStaff(): array
    {
        return [self::PlatformAdmin, self::SupportAgent];
    }

    /**
     * Permission keys this role grants. Enforced server-side via Gate.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::PlatformAdmin => ['*'],
            self::SupportAgent => [
                Permission::ViewAdminPanel->value,
                Permission::ManageSupportTickets->value,
                Permission::ModerateReviews->value,
                Permission::ViewAnalytics->value,
                Permission::OverseeBookings->value,
                Permission::ModerateClinics->value,
                Permission::ManageUsers->value,
                Permission::ViewErrorReports->value,
            ],
            self::ClinicOwner => [
                Permission::ManageOwnClinic->value,
                Permission::ManageOwnClinicBilling->value,
                Permission::ManageBookings->value,
            ],
            self::Doctor => [
                Permission::ManageOwnSchedule->value,
                Permission::ManageBookings->value,
            ],
            self::Reception => [
                Permission::ManageBookings->value,
            ],
            self::Patient => [],
        };
    }
}
