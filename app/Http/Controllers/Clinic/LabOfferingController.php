<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\ClinicLabOffering;
use App\Models\LabPackage;
use App\Models\LabTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabOfferingController extends Controller
{
    use ResolvesClinicContext;

    public function edit(Request $request): View
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);
        $offerings = $clinic->labOfferings()->get()->groupBy('item_type');

        return view('clinic.labs.edit', [
            'clinic' => $clinic,
            'tests' => LabTest::query()->active()->ordered()->get(),
            'packages' => LabPackage::query()->active()->with('tests')->ordered()->get(),
            'testOfferings' => $offerings->get('test', collect())->keyBy('lab_test_id'),
            'packageOfferings' => $offerings->get('package', collect())->keyBy('lab_package_id'),
            'access' => $this->access($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);
        $payload = $request->validate([
            'tests' => ['array'],
            'tests.*.enabled' => ['sometimes', 'boolean'],
            'tests.*.price' => ['nullable', 'numeric', 'min:0'],
            'tests.*.promo_price' => ['nullable', 'numeric', 'min:0'],
            'tests.*.home_price' => ['nullable', 'numeric', 'min:0'],
            'tests.*.home' => ['sometimes', 'boolean'],
            'packages' => ['array'],
            'packages.*.enabled' => ['sometimes', 'boolean'],
            'packages.*.price' => ['nullable', 'numeric', 'min:0'],
            'packages.*.promo_price' => ['nullable', 'numeric', 'min:0'],
            'packages.*.home_price' => ['nullable', 'numeric', 'min:0'],
            'packages.*.home' => ['sometimes', 'boolean'],
        ]);

        $this->syncOfferings($clinic->id, 'test', 'lab_test_id', $payload['tests'] ?? []);
        $this->syncOfferings($clinic->id, 'package', 'lab_package_id', $payload['packages'] ?? []);

        return back()->with('status', __('common.updated_successfully'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncOfferings(int $clinicId, string $type, string $foreignKey, array $rows): void
    {
        foreach ($rows as $id => $row) {
            $enabled = (bool) ($row['enabled'] ?? false);

            if (! $enabled) {
                ClinicLabOffering::query()
                    ->where('clinic_id', $clinicId)
                    ->where($foreignKey, $id)
                    ->delete();

                continue;
            }

            ClinicLabOffering::query()->updateOrCreate(
                ['clinic_id' => $clinicId, $foreignKey => $id],
                [
                    'item_type' => $type,
                    'price' => $row['price'] ?? 0,
                    'promo_price' => filled($row['promo_price'] ?? null) ? $row['promo_price'] : null,
                    'home_price' => filled($row['home_price'] ?? null) ? $row['home_price'] : null,
                    'allows_home_collection' => (bool) ($row['home'] ?? false),
                    'is_active' => true,
                ],
            );
        }
    }
}
