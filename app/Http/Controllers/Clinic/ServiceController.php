<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
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

        $clinic = $this->clinic($request)->load(['plan.featureFlags', 'services.insuranceProviders']);

        $types = ServiceType::query()->active()->ordered()->get()
            ->filter(fn (ServiceType $type) => $clinic->moduleAllowsServiceType($type->code))
            ->values();

        return view('clinic.services.edit', [
            'clinic' => $clinic,
            'types' => $types,
            'enabled' => $clinic->services->keyBy('service_type_id'),
            'insuranceProviders' => InsuranceProvider::query()->active()->ordered()->get(),
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
            'services.*.session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'services.*.requires_evaluation_first' => ['sometimes', 'boolean'],
            'services.*.insurance_providers' => ['array'],
            'services.*.insurance_providers.*' => ['integer', 'exists:insurance_providers,id'],
        ]);

        $types = ServiceType::query()->active()->get()->keyBy('id');
        $activeProviderIds = InsuranceProvider::query()->active()->pluck('id');

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
                'duration_minutes' => filled($row['duration_minutes'] ?? null)
                    ? $row['duration_minutes']
                    : $type->default_duration_minutes,
                'session_count' => max(1, min(30, (int) ($row['session_count'] ?? 1))),
                'is_active' => $enabled,
                'requires_evaluation_first' => $request->boolean('services.'.$type->id.'.requires_evaluation_first'),
            ]);
            $service->save();

            $service->insuranceProviders()->sync(
                $activeProviderIds->intersect($row['insurance_providers'] ?? [])->all()
            );
        }

        return back()->with('status', __('common.updated_successfully'));
    }
}
