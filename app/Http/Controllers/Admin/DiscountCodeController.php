<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\SubscriptionPlan;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiscountCodeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageDiscountCodes->value];
    }

    public function index(): View
    {
        return view('admin.discount-codes.index', [
            'codes' => DiscountCode::with('subscriptionPlan')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.discount-codes.create', [
            'code' => new DiscountCode(['is_active' => true, 'discount_type' => 'percentage']),
            'plans' => SubscriptionPlan::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $code = DiscountCode::create($this->validated($request));

        Audit::created($code);

        return redirect()->route('admin.discount-codes.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(DiscountCode $discountCode): View
    {
        return view('admin.discount-codes.edit', [
            'code' => $discountCode,
            'plans' => SubscriptionPlan::ordered()->get(),
        ]);
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $before = $discountCode->getOriginal();
        $discountCode->update($this->validated($request, $discountCode));

        Audit::updated($discountCode, $before);

        return redirect()->route('admin.discount-codes.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        Audit::deleted($discountCode);
        $discountCode->delete();

        return redirect()->route('admin.discount-codes.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function validated(Request $request, ?DiscountCode $code = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('discount_codes', 'code')->ignore($code)],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:999999'],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'is_active' => ['boolean'],
        ]);

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            $data['discount_value'] = 100;
        }

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
