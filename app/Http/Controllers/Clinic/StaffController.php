<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    use ResolvesClinicContext;

    public function index(Request $request): View
    {
        $this->authorizeStaff($request);

        $clinic = $this->clinic($request);
        $reception = RoleName::Reception->value;

        $staff = User::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', $reception)
                ->where('role_user.clinic_id', $clinic->id))
            ->orderBy('name')
            ->get();

        return view('clinic.staff.index', [
            'clinic' => $clinic,
            'staff' => $staff,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeStaff($request);

        return view('clinic.staff.create', [
            'clinic' => $this->clinic($request),
            'member' => new User(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeStaff($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', Password::min(8)],
        ]);

        $phone = User::normalizePhone($validated['phone']);

        $request->merge(['phone' => $phone]);
        $request->validate(
            ['phone' => [Rule::unique('users', 'phone')]],
            [],
            ['phone' => __('auth.phone')],
        );

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'password' => $validated['password'],
            'preferred_language' => app()->getLocale(),
            'is_active' => true,
        ]);

        $user->assignRole(RoleName::Reception, $this->clinic($request)->id);

        return redirect()->route('clinic.staff.index')->with('status', __('common.created_successfully'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeStaff($request);

        abort_unless($user->hasRole(RoleName::Reception, $this->clinic($request)->id), 404);

        $user->removeRole(RoleName::Reception);

        if ($user->roles()->count() === 0 && $user->ownedClinics()->doesntExist()) {
            $user->delete();
        }

        return back()->with('status', __('common.deleted_successfully'));
    }
}
