<?php

namespace App\Http\Controllers\Clinic;

use App\Models\Clinic;
use App\Support\ClinicAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesClinicContext
{
    protected function clinic(Request $request): Clinic
    {
        return $request->attributes->get('clinic');
    }

    protected function access(Request $request): ClinicAccess
    {
        return $request->attributes->get('clinicAccess');
    }

    protected function authorizeManage(Request $request): void
    {
        abort_unless($this->access($request)->canManage(), 403);
    }

    protected function authorizeBilling(Request $request): void
    {
        abort_unless($this->access($request)->canManageBilling(), 403);
    }

    protected function authorizeStaff(Request $request): void
    {
        abort_unless($this->access($request)->canManageStaff(), 403);
    }

    /**
     * Hide another clinic's records rather than acknowledging they exist.
     */
    protected function assertOwned(Request $request, Model $model, string $foreignKey = 'clinic_id'): void
    {
        abort_unless((int) $model->{$foreignKey} === (int) $this->clinic($request)->id, 404);
    }
}
