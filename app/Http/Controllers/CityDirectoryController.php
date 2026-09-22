<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Governorate;
use Illuminate\View\View;

class CityDirectoryController extends Controller
{
    public function index(): View
    {
        $governorates = Governorate::query()
            ->active()
            ->ordered()
            ->with(['cities' => fn ($query) => $query->active()->ordered()])
            ->get();

        return view('cities.index', compact('governorates'));
    }

    public function show(City $city): View
    {
        abort_unless($city->is_active, 404);

        $city->load('governorate');

        $clinics = Clinic::query()
            ->listable()
            ->whereHas('addresses', fn ($query) => $query->where('city_id', $city->id)->where('is_active', true))
            ->with(['primaryAddress.city', 'doctors.specialty'])
            ->orderBy('name_ar')
            ->limit(12)
            ->get();

        $doctors = Doctor::query()
            ->active()
            ->whereHas('clinics.addresses', fn ($query) => $query->where('city_id', $city->id))
            ->with(['specialty', 'clinics.addresses.city'])
            ->orderBy('name_ar')
            ->limit(12)
            ->get();

        return view('cities.show', compact('city', 'clinics', 'doctors'));
    }
}
