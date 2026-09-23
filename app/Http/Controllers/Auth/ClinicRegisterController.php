<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ClinicModule;
use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Governorate;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ClinicRegistrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ClinicRegisterController extends Controller
{
    public function create(): View
    {
        $governorates = Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get();

        return view('auth.register-clinic', [
            'governorates' => $governorates,
            'citiesByGovernorate' => $governorates->mapWithKeys(fn ($governorate) => [
                $governorate->id => $governorate->cities->map(fn ($city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                ])->values(),
            ]),
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'plans' => SubscriptionPlan::query()->active()->ordered()->with('featureFlags')->get(),
            'modules' => ClinicModule::selectable(),
        ]);
    }

    public function store(Request $request, ClinicRegistrar $registrar): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_type' => ['required', Rule::in(['solo', 'multi'])],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'clinic_name_ar' => ['required', 'string', 'max:160'],
            'clinic_name_en' => ['required', 'string', 'max:160'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('governorate_id', $request->integer('governorate_id')),
            ],
            'address_line' => ['required', 'string', 'max:255'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'discount_code' => ['nullable', 'string', 'max:64'],
            'doctor_name_ar' => ['nullable', 'string', 'max:160'],
            'doctor_name_en' => ['nullable', 'string', 'max:160'],
            'modules' => ['nullable', 'array'],
            'modules.*' => [Rule::enum(ClinicModule::class)],
        ]);

        $phone = User::normalizePhone($validated['phone']);

        $request->validate(
            ['phone' => [Rule::unique('users', 'phone')->where(fn ($q) => $q->where('phone', $phone))]],
            [],
            ['phone' => __('auth.phone')],
        );

        if (filled($validated['discount_code'] ?? null)) {
            $plan = SubscriptionPlan::query()->find($validated['subscription_plan_id'] ?? null)
                ?? SubscriptionPlan::query()->where('is_default_free', true)->first();

            $discount = DiscountCode::query()
                ->where('code', strtoupper(trim($validated['discount_code'])))
                ->first();

            if (! $discount || ! $plan || ! $discount->isRedeemable($plan)) {
                return back()->withInput()->withErrors([
                    'discount_code' => __('clinic.register.invalid_discount'),
                ]);
            }
        }

        $user = $registrar->register([
            ...$validated,
            'phone' => $phone,
            'is_single_doctor' => $validated['clinic_type'] === 'solo',
            'preferred_language' => app()->getLocale(),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('clinic.dashboard')
            ->with('status', __('clinic.register.welcome'));
    }
}
