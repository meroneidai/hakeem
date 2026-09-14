<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    use ResolvesClinicContext;

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        return view('clinic.doctors.index', [
            'clinic' => $clinic,
            'doctors' => $clinic->doctors()->with('specialty')->orderBy('name_ar')->get(),
            'access' => $this->access($request),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManage($request);

        abort_unless($this->clinic($request)->canAddDoctor(), 403, __('clinic.doctors.cap_reached'));

        return view('clinic.doctors.create', $this->formData($request, new Doctor(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);

        abort_unless($clinic->canAddDoctor(), 403, __('clinic.doctors.cap_reached'));

        $validated = $this->validated($request);

        $doctor = Doctor::create([
            ...$validated,
            'slug' => UniqueSlug::for($validated['name_en'], 'doctors'),
        ]);

        $clinic->doctors()->attach($doctor->id);
        $this->syncAvailability($request, $doctor, $clinic->id);

        return redirect()->route('clinic.doctors.index')->with('status', __('common.created_successfully'));
    }

    public function edit(Request $request, Doctor $doctor): View
    {
        $this->assertDoctorInClinic($request, $doctor);
        abort_unless($this->access($request)->canManageDoctor($doctor), 403);

        $doctor->load('availability');

        return view('clinic.doctors.edit', $this->formData($request, $doctor));
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $this->assertDoctorInClinic($request, $doctor);
        abort_unless($this->access($request)->canManageDoctor($doctor), 403);

        $validated = $this->validated($request);
        $validated['slug'] = UniqueSlug::for($validated['name_en'], 'doctors', 'slug', $doctor->id);

        $doctor->update($validated);

        if ($this->access($request)->canManage()) {
            $this->syncAvailability($request, $doctor, $this->clinic($request)->id);
        }

        return back()->with('status', __('common.updated_successfully'));
    }

    public function destroy(Request $request, Doctor $doctor): RedirectResponse
    {
        $this->assertDoctorInClinic($request, $doctor);
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);

        if ($clinic->doctors()->count() <= 1) {
            return back()->with('error', __('clinic.doctors.cannot_delete_last'));
        }

        $clinic->doctors()->detach($doctor->id);

        if ($doctor->clinics()->count() === 0) {
            $doctor->availability()->delete();
            $doctor->delete();
        }

        return redirect()->route('clinic.doctors.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, Doctor $doctor): array
    {
        $clinic = $this->clinic($request);

        return [
            'clinic' => $clinic,
            'doctor' => $doctor,
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'addresses' => $clinic->addresses()->with('schedules')->ordered()->get(),
            'days' => DayOfWeek::weekOrder(),
            'selectedAddressIds' => $doctor->exists
                ? $doctor->availability->pluck('clinic_address_id')->unique()->values()->all()
                : $clinic->addresses()->pluck('id')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'bio_ar' => ['nullable', 'string', 'max:4000'],
            'bio_en' => ['nullable', 'string', 'max:4000'],
            'credentials' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function syncAvailability(Request $request, Doctor $doctor, int $clinicId): void
    {
        $addressIds = collect($request->input('address_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();

        $owned = $this->clinic($request)->addresses()->whereIn('id', $addressIds)->pluck('id');

        $doctor->availability()->where('clinic_address_id', '!=', 0)->delete();
        $doctor->availability()->delete();

        foreach ($owned as $addressId) {
            $address = $this->clinic($request)->addresses()->with('schedules')->find($addressId);

            foreach ($address?->schedules ?? [] as $schedule) {
                if ($schedule->is_closed) {
                    continue;
                }

                $doctor->availability()->create([
                    'clinic_address_id' => $addressId,
                    'day_of_week' => $schedule->day_of_week,
                    'open_time' => $schedule->open_time,
                    'close_time' => $schedule->close_time,
                ]);
            }
        }
    }

    private function assertDoctorInClinic(Request $request, Doctor $doctor): void
    {
        abort_unless($this->clinic($request)->doctors()->where('doctors.id', $doctor->id)->exists(), 404);
    }
}
