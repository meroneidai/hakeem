<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\Specialty;
use App\Support\PublicImage;
use App\Support\SearchQuery;
use App\Support\SeoDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:140'],
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
            'gender' => ['nullable', 'in:male,female'],
            'min_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
        ]);

        $doctors = Doctor::query()
            ->listable()
            ->withRatings()
            ->with(['specialty', 'clinics' => fn ($clinics) => $clinics->listable()->with('addresses.city.governorate')])
            ->when($filters['q'] ?? null, function ($query, $term) {
                SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%")
                        ->orWhereLike('credentials', "%{$token}%");
                });
            })
            ->when($filters['specialty'] ?? null, fn ($query, $slug) => $query->whereHas(
                'specialty',
                fn ($specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinics.addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinics.addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when($filters['gender'] ?? null, fn ($query, $gender) => $query->where('gender', $gender))
            ->when($filters['min_experience'] ?? null, fn ($query, $years) => $query->where('years_of_experience', '>=', $years))
            ->orderBy('name_ar')
            ->paginate(12)
            ->withQueryString();

        return view('doctors.index', [
            'doctors' => $doctors,
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'governorates' => Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Doctor $doctor): View
    {
        abort_unless($doctor->is_active && $doctor->clinics()->listable()->exists(), 404);

        $doctor->load([
            'specialty',
            'availability.address.city',
            'availability.address.schedules',
            'reviews' => fn ($query) => $query->visible()->latest()->with('patient')->limit(8),
            'clinics' => fn ($query) => $query->listable()->with([
                'primaryAddress.city',
                'addresses' => fn ($addresses) => $addresses->active()->with(['city', 'schedules']),
                'services' => fn ($services) => $services->active()->with('serviceType'),
            ]),
        ]);
        $doctor->loadCount(['reviews as rating_count' => fn ($query) => $query->visible()]);
        $doctor->loadAvg(['reviews as rating_average' => fn ($query) => $query->visible()], 'overall');

        return view('doctors.show', [
            'doctor' => $doctor,
            'seoDescription' => $doctor->translated('bio') ?: $doctor->specialty?->name,
            'seoImage' => PublicImage::url($doctor->profile_photo_path),
            'jsonLd' => [
                SeoDocument::breadcrumbs([
                    ['name' => __('discover.nav.home'), 'url' => route('home')],
                    ['name' => __('discover.nav.doctors'), 'url' => route('doctors.index')],
                    ['name' => $doctor->name, 'url' => route('doctors.show', $doctor)],
                ]),
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Physician',
                    'name' => $doctor->name,
                    'url' => route('doctors.show', $doctor),
                    'image' => PublicImage::url($doctor->profile_photo_path),
                    'description' => $doctor->translated('bio'),
                    'medicalSpecialty' => $doctor->specialty?->name,
                    'yearsOfExperience' => $doctor->years_of_experience,
                ]),
            ],
        ]);
    }
}
