<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\SubscriptionPlan;
use App\Services\ClinicSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    use ResolvesClinicContext;

    public function edit(Request $request): View
    {
        $this->authorizeBilling($request);

        $clinic = $this->clinic($request)->load(['plan.featureFlags', 'currentSubscription.plan']);

        return view('clinic.subscription.edit', [
            'clinic' => $clinic,
            'plans' => SubscriptionPlan::query()->active()->ordered()->with('featureFlags')->get(),
        ]);
    }

    public function update(Request $request, ClinicSubscriptionService $subscriptions): RedirectResponse
    {
        $this->authorizeBilling($request);

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'discount_code' => ['nullable', 'string', 'max:64'],
        ]);

        $plan = SubscriptionPlan::query()->active()->findOrFail($validated['subscription_plan_id']);
        $cycle = BillingCycle::from($validated['billing_cycle']);

        $discount = null;

        if (filled($validated['discount_code'] ?? null)) {
            $discount = DiscountCode::query()
                ->where('code', strtoupper(trim($validated['discount_code'])))
                ->first();

            if (! $discount || ! $discount->isRedeemable($plan)) {
                return back()->withInput()->withErrors([
                    'discount_code' => __('clinic.register.invalid_discount'),
                ]);
            }
        }

        $subscriptions->subscribe($this->clinic($request), $plan, $cycle, $discount);

        return back()->with('status', __('clinic.subscription.updated'));
    }
}
