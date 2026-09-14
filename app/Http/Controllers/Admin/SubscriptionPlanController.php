<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSubscriptionPlans->value];
    }

    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => SubscriptionPlan::with('featureFlags')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', [
            'plan' => new SubscriptionPlan(['is_active' => true]),
            'features' => $this->groupedFeatures(),
            'enabled' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = DB::transaction(function () use ($request) {
            $plan = SubscriptionPlan::create($this->validated($request));
            $this->syncFeatures($plan, $request);

            return $plan;
        });

        Audit::created($plan);

        return redirect()->route('admin.plans.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(SubscriptionPlan $plan): View
    {
        return view('admin.plans.edit', [
            'plan' => $plan->load('featureFlags'),
            'features' => $this->groupedFeatures(),
            'enabled' => $plan->featureFlags->where('is_enabled', true)->pluck('feature_code')->all(),
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $before = $plan->getOriginal();

        DB::transaction(function () use ($request, $plan) {
            $plan->update($this->validated($request, $plan));
            $this->syncFeatures($plan, $request);
        });

        Audit::updated($plan, $before);

        return redirect()->route('admin.plans.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->is_default_free) {
            return back()->with('error', __('admin.plans.default_free_undeletable'));
        }

        Audit::deleted($plan);
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, list<PlanFeature>>
     */
    private function groupedFeatures(): array
    {
        $grouped = [];

        foreach (PlanFeature::cases() as $feature) {
            $grouped[$feature->group()][] = $feature;
        }

        return $grouped;
    }

    private function syncFeatures(SubscriptionPlan $plan, Request $request): void
    {
        $selected = collect($request->input('features', []))->filter()->keys()->all();

        foreach (PlanFeature::cases() as $feature) {
            $plan->featureFlags()->updateOrCreate(
                ['feature_code' => $feature->value],
                ['is_enabled' => in_array($feature->value, $selected, true)],
            );
        }

        $plan->unsetRelation('featureFlags');
    }

    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($plan)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'yearly_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'yearly_discount_pct' => ['nullable', 'integer', 'min:0', 'max:100'],
            'booking_cap' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'doctor_cap' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'address_cap' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'is_default_free' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['yearly_price'] ??= 0;
        $data['yearly_discount_pct'] ??= 0;
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default_free'] = $request->boolean('is_default_free');

        // Exactly one plan may be the auto-assigned free tier.
        if ($data['is_default_free']) {
            SubscriptionPlan::when($plan, fn ($query) => $query->whereKeyNot($plan->getKey()))
                ->update(['is_default_free' => false]);
        }

        return $data;
    }
}
