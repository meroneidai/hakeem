<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\PlanFeature;
use App\Enums\RoleName;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;

/**
 * Per-request capability check for one user inside one clinic.
 * Role membership is clinic-scoped; a reception account at clinic A must never
 * see clinic B even though both share the same permission keys.
 */
class ClinicAccess
{
    public function __construct(public User $user, public Clinic $clinic) {}

    public function canView(): bool
    {
        return $this->user->belongsToClinic($this->clinic);
    }

    public function isOwner(): bool
    {
        return $this->user->id === $this->clinic->owner_user_id
            || $this->user->hasRole(RoleName::ClinicOwner, $this->clinic->id);
    }

    public function isReception(): bool
    {
        return $this->user->hasRole(RoleName::Reception, $this->clinic->id);
    }

    public function isDoctor(): bool
    {
        return $this->user->hasRole(RoleName::Doctor, $this->clinic->id);
    }

    /**
     * Profile, doctors, addresses, services — clinic-owner only.
     */
    public function canManage(): bool
    {
        return $this->isOwner();
    }

    public function canManageBilling(): bool
    {
        return $this->isOwner() && $this->user->hasPermission(Permission::ManageOwnClinicBilling);
    }

    public function canManageStaff(): bool
    {
        return $this->isOwner() && $this->clinic->allows(PlanFeature::ReceptionRole);
    }

    public function canManageDoctor(Doctor $doctor): bool
    {
        if ($this->isOwner()) {
            return $this->clinic->doctors->contains('id', $doctor->id);
        }

        return $this->isDoctor()
            && $doctor->user_id === $this->user->id
            && $this->clinic->doctors->contains('id', $doctor->id);
    }
}
