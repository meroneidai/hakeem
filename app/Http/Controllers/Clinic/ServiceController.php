<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ResolvesClinicContext;

    public function edit(Request $request): View
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request)->load(['plan.featureFlags', 'services']);

        $types = ServiceType::query()->active()->ordered()->get();

        return view('clinic.services.edit', [
            'clinic' => $clinic,
            'types' => $types,
            'enabled' => $clinic->services->keyBy('service_type_id'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request)->load('plan.featureFlags');

        $payload = $request->validate([
            'services' => ['array'],
            'services.*.enabled' => ['sometimes', 'boolean'],
            'services.*.price' => ['nullable', 'numeric', 'min:0'],
            'services.*.promo_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $types = ServiceType::query()->active()->get()->keyBy('id');

        foreach ($payload['services'] ?? [] as $typeId => $row) {
            $type = $types->get((int) $typeId);

            if (! $type) {
                continue;
            }

            $feature = PlanFeature::forServiceType($type->code);

            if ($feature && ! $clinic->allows($feature)) {
                continue;
            }

            $enabled = (bool) ($row['enabled'] ?? false);

            $service = $clinic->services()->where('service_type_id', $type->id)->first()
                ?? $clinic->services()->make(['service_type_id' => $type->id]);

            $service->fill([
                'price' => $row['price'] ?? 0,
                'promo_price' => filled($row['promo_price'] ?? null) ? $row['promo_price'] : null,
                'duration_minutes' => filled($row['duration_minutes'] ?? null) ? $row['duration_minutes'] : null,
                'is_active' => $enabled,
            ]);
            $service->save();
        }

        return back()->with('status', __('common.updated_successfully'));
    }
}
