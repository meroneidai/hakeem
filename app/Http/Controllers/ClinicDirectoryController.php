<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Governorate;
use App\Models\Specialty;
use App\Support\PublicImage;
use App\Support\SearchQuery;
use App\Support\SeoDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:140'],
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
        ]);

        $clinics = Clinic::query()
            ->listable()
            ->withRatings()
            ->with(['primaryAddress.city.governorate', 'addresses', 'doctors.specialty', 'services' => fn ($services) => $services->active()])
            ->withCount(['doctors', 'addresses'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%");
                });
            })
            ->when($filters['specialty'] ?? null, fn ($query, $slug) => $query->whereHas(
                'doctors.specialty',
                fn ($specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->orderBy('name_ar')
            ->paginate(12)
            ->withQueryString();

        $mapPins = $clinics->getCollection()
            ->flatMap(function (Clinic $clinic) {
                return $clinic->addresses
                    ->filter(fn ($address) => $address->latitude && $address->longitude)
                    ->map(fn ($address) => [
                        'lat' => (float) $address->latitude,
                        'lng' => (float) $address->longitude,
                        'name' => $clinic->name,
                    ]);
            })
            ->values();

        return view('clinics.index', [
            'clinics' => $clinics,
            'mapPins' => $mapPins,
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'governorates' => Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Clinic $clinic): View
    {
        abort_unless($clinic->is_active && $clinic->isVerified(), 404);

        $clinic->load([
            'primaryAddress.city',
            'doctors' => fn ($query) => $query->active()->with(['specialty', 'clinics.addresses.city']),
            'addresses' => fn ($query) => $query->active()->with(['city.governorate', 'schedules']),
            'services' => fn ($query) => $query->active()->with('serviceType'),
            'promotions' => fn ($query) => $query->running(),
            'reviews' => fn ($query) => $query->visible()->latest()->with('patient')->limit(8),
        ]);

        $clinic->loadCount(['reviews as rating_count' => fn ($query) => $query->visible()]);
        $clinic->loadAvg(['reviews as rating_average' => fn ($query) => $query->visible()], 'overall');

        return view('clinics.show', [
            'clinic' => $clinic,
            'seoDescription' => $clinic->description,
            'seoImage' => PublicImage::url($clinic->logo_path),
            'jsonLd' => [
                SeoDocument::breadcrumbs([
                    ['name' => __('discover.nav.home'), 'url' => route('home')],
                    ['name' => __('discover.nav.clinics'), 'url' => route('clinics.index')],
                    ['name' => $clinic->name, 'url' => route('clinics.show', $clinic)],
                ]),
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'MedicalClinic',
                    'name' => $clinic->name,
                    'url' => route('clinics.show', $clinic),
                    'image' => PublicImage::url($clinic->logo_path),
                    'description' => $clinic->description,
                    'address' => $clinic->primaryAddress?->city?->name,
                    'telephone' => $clinic->phone,
                ]),
            ],
        ]);
    }
}
