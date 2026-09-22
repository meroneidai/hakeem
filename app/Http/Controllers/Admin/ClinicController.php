<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClinicModule;
use App\Enums\Permission;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ModerateClinics->value];
    }

    public function index(Request $request): View
    {
        $clinics = Clinic::query()
            ->with(['owner', 'plan', 'primaryAddress.city', 'currentSubscription'])
            ->withCount(['doctors', 'bookings', 'addresses'])
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $query->where(fn ($inner) => $inner
                    ->whereLike('name_ar', "%{$term}%")
                    ->orWhereLike('name_en', "%{$term}%")
                    ->orWhereLike('phone', "%{$term}%"));
            })
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('verification_status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.clinics.index', [
            'clinics' => $clinics,
            'statuses' => VerificationStatus::cases(),
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function show(Clinic $clinic): View
    {
        $clinic->load([
            'owner',
            'plan',
            'currentSubscription.plan',
            'addresses.city.governorate',
            'doctors.specialty',
            'subscriptions.plan',
        ]);
        $clinic->loadCount(['bookings', 'doctors']);

        $bookings = $clinic->bookings()
            ->with(['patient', 'doctor', 'serviceType'])
            ->latest('scheduled_at')
            ->limit(15)
            ->get();

        return view('admin.clinics.show', [
            'clinic' => $clinic,
            'bookings' => $bookings,
            'modules' => ClinicModule::selectable(),
        ]);
    }

    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        $data = $request->validate([
            'verification_status' => ['required', Rule::enum(VerificationStatus::class)],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
            'modules' => ['nullable', 'array'],
            'modules.*' => [Rule::enum(ClinicModule::class)],
        ]);

        $status = VerificationStatus::from($data['verification_status']);

        $clinic->update([
            'verification_status' => $status,
            'rejection_reason' => $status === VerificationStatus::Rejected ? ($data['rejection_reason'] ?? null) : null,
            'verified_at' => $status === VerificationStatus::Verified ? now() : $clinic->verified_at,
            'is_active' => $status !== VerificationStatus::Suspended && $status !== VerificationStatus::Rejected,
            'modules' => $request->boolean('modules_present')
                ? ClinicModule::normalize($request->input('modules', []))
                : $clinic->modules,
        ]);

        Audit::updated($clinic, []);

        return back()->with('status', __('common.updated_successfully'));
    }
}
