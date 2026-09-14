<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageStaff->value];
    }

    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::with('roles')
                ->whereHas('roles', fn ($query) => $query->whereIn('name', $this->internalRoleNames()))
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'member' => new User(['is_active' => true, 'preferred_language' => 'ar']),
            'roles' => $this->internalRoles(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $member = User::create([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'],
            'password' => $data['password'],
            'preferred_language' => $data['preferred_language'],
            'is_active' => $data['is_active'],
        ]);

        $this->syncRoles($member, $data['roles']);

        Audit::created($member);

        return redirect()->route('admin.staff.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(User $staff): View
    {
        return view('admin.staff.edit', [
            'member' => $staff->load('roles'),
            'roles' => $this->internalRoles(),
            'assigned' => $staff->roles->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $data = $this->validated($request, $staff);
        $before = $staff->getOriginal();

        $staff->fill([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'],
            'preferred_language' => $data['preferred_language'],
            'is_active' => $data['is_active'],
        ]);

        if (filled($data['password'] ?? null)) {
            $staff->password = $data['password'];
        }

        $staff->save();

        // Admins can't strip their own access and lock themselves out.
        if ($staff->isNot($request->user())) {
            $this->syncRoles($staff, $data['roles']);
        }

        Audit::updated($staff, $before);

        return redirect()->route('admin.staff.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        if ($staff->is($request->user())) {
            return back()->with('error', __('admin.staff.cannot_edit_self_roles'));
        }

        Audit::deleted($staff);
        $staff->delete();

        return redirect()->route('admin.staff.index')
            ->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return list<string>
     */
    private function internalRoleNames(): array
    {
        return array_map(fn (RoleName $role) => $role->value, RoleName::internalStaff());
    }

    private function internalRoles()
    {
        return Role::whereIn('name', $this->internalRoleNames())->get();
    }

    private function syncRoles(User $member, array $roleNames): void
    {
        $ids = Role::whereIn('name', $roleNames)->pluck('id');

        $member->roles()->sync(
            $ids->mapWithKeys(fn ($id) => [$id => ['clinic_id' => null]])->all()
        );

        $member->unsetRelation('roles');
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($staff)],
            'password' => [$staff ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'preferred_language' => ['required', Rule::in(array_keys(config('hakeem.locales')))],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($this->internalRoleNames())],
            'is_active' => ['boolean'],
        ]);

        $normalized = User::normalizePhone($data['phone']);

        $request->validate([
            'phone' => [Rule::unique('users', 'phone')->ignore($staff)->where(fn ($q) => $q->where('phone', $normalized))],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
