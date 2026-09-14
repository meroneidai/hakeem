<?php

namespace App\Models\Concerns;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /** @var list<string>|null */
    protected ?array $resolvedPermissions = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('clinic_id')
            ->withTimestamps();
    }

    public function hasRole(RoleName|string $role, ?int $clinicId = null): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        return $this->roles
            ->filter(fn (Role $r) => $r->name === $name)
            ->when($clinicId !== null, fn ($roles) => $roles->filter(
                fn (Role $r) => (int) $r->pivot->clinic_id === $clinicId
            ))
            ->isNotEmpty();
    }

    public function hasAnyRole(RoleName|string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function assignRole(RoleName|string $role, ?int $clinicId = null): void
    {
        $name = $role instanceof RoleName ? $role->value : $role;
        $model = Role::where('name', $name)->firstOrFail();

        $this->roles()->syncWithoutDetaching([
            $model->id => ['clinic_id' => $clinicId],
        ]);

        $this->unsetRelation('roles');
        $this->resolvedPermissions = null;
        $this->accessibleClinicsCache = null;
    }

    public function removeRole(RoleName|string $role): void
    {
        $name = $role instanceof RoleName ? $role->value : $role;
        $model = Role::where('name', $name)->first();

        if ($model) {
            $this->roles()->detach($model->id);
            $this->unsetRelation('roles');
            $this->resolvedPermissions = null;
        }
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        $permissions = $this->roles
            ->flatMap(fn (Role $role) => $role->enum()?->permissions() ?? [])
            ->unique()
            ->values()
            ->all();

        return $this->resolvedPermissions = $permissions;
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $permissions = $this->permissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array(
            $permission instanceof Permission ? $permission->value : $permission,
            $permissions,
            true,
        );
    }

    public function isPlatformAdmin(): bool
    {
        return $this->hasRole(RoleName::PlatformAdmin);
    }

    public function isInternalStaff(): bool
    {
        return $this->hasAnyRole(...RoleName::internalStaff());
    }
}
