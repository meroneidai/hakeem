<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageServiceTypes->value];
    }

    public function index(): View
    {
        return view('admin.service-types.index', [
            'serviceTypes' => ServiceType::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.service-types.create', [
            'serviceType' => new ServiceType([
                'is_active' => true,
                'requires_clinic_address' => true,
                'requires_time_slot' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $serviceType = ServiceType::create($this->validated($request));

        Audit::created($serviceType);

        return redirect()->route('admin.service-types.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(ServiceType $serviceType): View
    {
        return view('admin.service-types.edit', compact('serviceType'));
    }

    public function update(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $before = $serviceType->getOriginal();
        $serviceType->update($this->validated($request, $serviceType));

        Audit::updated($serviceType, $before);

        return redirect()->route('admin.service-types.index')
            ->with('status', __('common.updated_successfully'));
    }

    private function validated(Request $request, ?ServiceType $serviceType = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', Rule::unique('service_types', 'code')->ignore($serviceType)],
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('service_types', 'slug')->ignore($serviceType)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:64'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['display_order'] ??= 0;

        foreach ([
            'requires_clinic_address',
            'requires_patient_address',
            'requires_time_slot',
            'is_online',
            'is_sensitive',
            'is_active',
        ] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        return $data;
    }
}
