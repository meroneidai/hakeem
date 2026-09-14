<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use App\Models\Governorate;
use App\Models\User;
use App\Services\AddressScheduleWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AddressController extends Controller
{
    use ResolvesClinicContext;

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        $addresses = $clinic->addresses()
            ->with(['city.governorate', 'schedules'])
            ->ordered()
            ->get();

        return view('clinic.addresses.index', [
            'clinic' => $clinic,
            'addresses' => $addresses,
            'access' => $this->access($request),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManage($request);

        abort_unless($this->clinic($request)->canAddAddress(), 403, __('clinic.addresses.cap_reached'));

        return view('clinic.addresses.create', $this->formData($request, new ClinicAddress(['is_active' => true])));
    }

    public function store(Request $request, AddressScheduleWriter $schedules): RedirectResponse
    {
        $this->authorizeManage($request);

        abort_unless($this->clinic($request)->canAddAddress(), 403, __('clinic.addresses.cap_reached'));

        $validated = $this->validated($request);
        $clinic = $this->clinic($request);

        if ($clinic->addresses()->count() === 0) {
            $validated['is_primary'] = true;
        }

        $address = $clinic->addresses()->create($validated);
        $schedules->seedDefaults($address);

        if ($request->boolean('is_primary')) {
            $this->markPrimary($clinic->id, $address->id);
        }

        return redirect()
            ->route('clinic.addresses.edit', $address)
            ->with('status', __('clinic.addresses.created_set_hours'));
    }

    public function edit(Request $request, ClinicAddress $address): View
    {
        $this->assertOwned($request, $address);
        $this->authorizeManage($request);

        $address->load(['city.governorate', 'schedules']);

        return view('clinic.addresses.edit', $this->formData($request, $address));
    }

    public function update(Request $request, ClinicAddress $address, AddressScheduleWriter $schedules): RedirectResponse
    {
        $this->assertOwned($request, $address);
        $this->authorizeManage($request);

        $validated = $this->validated($request, $address);
        $address->update($validated);

        $schedules->sync($address, $request->input('days', []));

        if ($request->boolean('is_primary')) {
            $this->markPrimary($address->clinic_id, $address->id);
        }

        return back()->with('status', __('common.updated_successfully'));
    }

    public function destroy(Request $request, ClinicAddress $address): RedirectResponse
    {
        $this->assertOwned($request, $address);
        $this->authorizeManage($request);

        if ($this->clinic($request)->addresses()->count() <= 1) {
            return back()->with('error', __('clinic.addresses.cannot_delete_last'));
        }

        $wasPrimary = $address->is_primary;
        $address->delete();

        if ($wasPrimary) {
            $this->clinic($request)->addresses()->ordered()->first()?->update(['is_primary' => true]);
        }

        return redirect()->route('clinic.addresses.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ClinicAddress $address): array
    {
        $governorates = Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get();

        return [
            'clinic' => $this->clinic($request),
            'address' => $address,
            'governorates' => $governorates,
            'citiesByGovernorate' => $governorates->mapWithKeys(fn ($governorate) => [
                $governorate->id => $governorate->cities->map(fn ($city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                ])->values(),
            ]),
            'days' => DayOfWeek::weekOrder(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ClinicAddress $address = null): array
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('governorate_id', $request->integer('governorate_id')),
            ],
            'label_ar' => ['nullable', 'string', 'max:80'],
            'label_en' => ['nullable', 'string', 'max:80'],
            'address_line' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (filled($data['phone'] ?? null)) {
            $data['phone'] = User::normalizePhone($data['phone']);
        }

        unset($data['governorate_id']);

        $data['is_primary'] = $request->boolean('is_primary');
        $data['is_active'] = $request->boolean('is_active', $address?->is_active ?? true);

        return $data;
    }

    private function markPrimary(int $clinicId, int $addressId): void
    {
        ClinicAddress::query()->where('clinic_id', $clinicId)->update(['is_primary' => false]);
        ClinicAddress::query()->where('id', $addressId)->update(['is_primary' => true]);
    }
}
