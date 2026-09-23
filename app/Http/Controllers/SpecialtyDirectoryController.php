<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\Specialty;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SpecialtyDirectoryController extends Controller
{
    public function index(): View
    {
        $specialties = Specialty::query()
            ->active()
            ->ordered()
            ->withCount(['doctors' => fn ($query) => $query->active()])
            ->get();

        return view('specialties.index', compact('specialties'));
    }

    public function show(Specialty $specialty): View
    {
        abort_unless($specialty->is_active, 404);

        $doctors = $this->doctorsFor($specialty);

        return view('specialties.show', [
            'specialty' => $specialty,
            'doctors' => $doctors,
            'place' => null,
            'cities' => $this->citiesWithDoctors($specialty),
        ]);
    }

    public function showLocation(Specialty $specialty, string $location): View
    {
        abort_unless($specialty->is_active, 404);

        $city = City::query()->active()->where('slug', $location)->first();

        if ($city) {
            return $this->cityView($specialty, $city);
        }

        $governorate = Governorate::query()->active()->where('slug', $location)->firstOrFail();

        return $this->governorateView($specialty, $governorate);
    }

    public function showCity(Specialty $specialty, Governorate $governorate, City $city): View
    {
        abort_unless($specialty->is_active && $city->is_active && (int) $city->governorate_id === (int) $governorate->id, 404);

        return $this->cityView($specialty, $city);
    }

    private function cityView(Specialty $specialty, City $city): View
    {
        $city->load('governorate');

        return view('specialties.show', [
            'specialty' => $specialty,
            'doctors' => $this->doctorsFor($specialty, cityId: $city->id),
            'place' => $city,
            'cities' => $this->citiesWithDoctors($specialty, $city->governorate_id, $city->id),
        ]);
    }

    private function governorateView(Specialty $specialty, Governorate $governorate): View
    {
        return view('specialties.show', [
            'specialty' => $specialty,
            'doctors' => $this->doctorsFor($specialty, governorateId: $governorate->id),
            'place' => $governorate,
            'cities' => $this->citiesWithDoctors($specialty, $governorate->id),
        ]);
    }

    /**
     * @return Collection<int, Doctor>
     */
    private function doctorsFor(Specialty $specialty, ?int $cityId = null, ?int $governorateId = null)
    {
        return Doctor::query()
            ->active()
            ->where('specialty_id', $specialty->id)
            ->when($cityId, fn ($query) => $query->whereHas(
                'clinics.addresses',
                fn ($addresses) => $addresses->where('city_id', $cityId)->where('is_active', true)
            ))
            ->when($governorateId, fn ($query) => $query->whereHas(
                'clinics.addresses.city',
                fn ($city) => $city->where('governorate_id', $governorateId)
            ))
            ->with(['specialty', 'clinics.addresses.city'])
            ->orderBy('name_ar')
            ->limit(24)
            ->get();
    }

    /**
     * @return Collection<int, City>
     */
    private function citiesWithDoctors(Specialty $specialty, ?int $governorateId = null, ?int $exceptCityId = null)
    {
        return City::query()
            ->active()
            ->ordered()
            ->when($governorateId, fn ($query) => $query->where('governorate_id', $governorateId))
            ->when($exceptCityId, fn ($query) => $query->whereKeyNot($exceptCityId))
            ->whereHas('addresses.clinic.doctors', fn ($doctors) => $doctors->active()->where('specialty_id', $specialty->id))
            ->limit(8)
            ->get();
    }
}
