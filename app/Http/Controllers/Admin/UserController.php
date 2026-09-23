<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoyaltyProgram;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageUsers->value];
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('city')
            ->withCount(['bookings', 'devices', 'referrals'])
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Patient->value))
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $query->where(fn ($inner) => $inner
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('phone', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%"));
            })
            ->when($request->string('phone')->value() === 'verified', fn ($query) => $query->whereNotNull('phone_verified_at'))
            ->when($request->string('phone')->value() === 'unverified', fn ($query) => $query->whereNull('phone_verified_at'))
            ->when($request->string('app')->value() === 'installed', fn ($query) => $query->whereNotNull('app_installed_at'))
            ->when($request->string('app')->value() === 'missing', fn ($query) => $query->whereNull('app_installed_at'))
            ->when($request->boolean('new'), fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $request->only(['q', 'phone', 'app', 'new']),
        ]);
    }

    public function show(User $user, LoyaltyProgram $loyalty): View
    {
        abort_unless($user->hasRole(RoleName::Patient), 404);

        $user->load(['city', 'insuranceProvider', 'devices', 'ownedClinics', 'referredBy']);
        $user->loadCount(['bookings', 'devices', 'referrals']);

        $bookings = $user->bookings()
            ->with(['clinic', 'doctor', 'serviceType'])
            ->latest('scheduled_at')
            ->limit(20)
            ->get();

        $ledgers = $user->walletLedgers()->latest('id')->limit(30)->get();

        return view('admin.users.show', [
            'user' => $user,
            'bookings' => $bookings,
            'ledgers' => $ledgers,
            'referralUrl' => $loyalty->referralUrl($user),
        ]);
    }

    public function update(Request $request, User $user, LoyaltyProgram $loyalty): RedirectResponse
    {
        abort_unless($user->hasRole(RoleName::Patient), 404);

        $action = $request->validate([
            'action' => ['required', 'in:toggle_active,verify_phone,adjust_wallet'],
            'amount' => ['required_if:action,adjust_wallet', 'nullable', 'numeric', 'max:100000'],
            'note' => ['nullable', 'string', 'max:190'],
        ])['action'];

        if ($action === 'toggle_active') {
            $user->update(['is_active' => ! $user->is_active]);
        }

        if ($action === 'verify_phone' && $user->phone_verified_at === null) {
            $user->update(['phone_verified_at' => now()]);
        }

        if ($action === 'adjust_wallet') {
            $loyalty->adjust(
                $user,
                (float) $request->input('amount'),
                $request->user(),
                $request->string('note')->trim()->value() ?: null,
            );
        }

        Audit::updated($user, []);

        return back()->with('status', __('common.updated_successfully'));
    }
}
