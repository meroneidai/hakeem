<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\ServiceTypeCode;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Support\SearchQuery;
use App\Support\SiteCopy;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ServiceDirectoryController extends Controller
{
    public function index(): View
    {
        $serviceTypes = ServiceType::query()->active()->ordered()->get();

        return view('services.index', compact('serviceTypes'));
    }

    public function show(Request $request, ServiceType $serviceType): View
    {
        abort_unless($serviceType->is_active, 404);

        return view('services.show', $this->directory($serviceType, $this->validatedFilters($request)));
    }

    public function showLocation(ServiceType $serviceType, string $location): View
    {
        abort_unless($serviceType->is_active, 404);

        $city = City::query()->active()->where('slug', $location)->first();

        if ($city) {
            return $this->cityView($serviceType, $city);
        }

        $governorate = Governorate::query()->active()->where('slug', $location)->firstOrFail();

        return $this->governorateView($serviceType, $governorate);
    }

    public function showCity(ServiceType $serviceType, Governorate $governorate, City $city): View
    {
        abort_unless($serviceType->is_active && $city->is_active && (int) $city->governorate_id === (int) $governorate->id, 404);

        return $this->cityView($serviceType, $city);
    }

    public function homeCare(SiteCopy $copy): View
    {
        return $this->landing(ServiceTypeCode::HomeVisit, 'home-care', 'pages.home_care', $copy);
    }

    public function teleconsultation(SiteCopy $copy): View
    {
        return $this->landing(ServiceTypeCode::VideoConsultation, 'teleconsultation', 'pages.teleconsultation', $copy);
    }

    private function landing(ServiceTypeCode $code, string $slug, string $copyKey, SiteCopy $copy): View
    {
        $serviceType = ServiceType::query()->active()->where('code', $code->value)->first();

        abort_unless($serviceType, 404);

        return view('services.landing', $this->directory($serviceType, []) + [
            'copyKey' => $copyKey,
            'heading' => $copy->heading($slug, $copyKey.'.heading'),
            'lead' => $copy->intro($slug, $copyKey.'.lead'),
        ]);
    }

    /**
     * @return array{
     *     q?: ?string,
     *     specialty?: ?string,
     *     governorate?: ?string,
     *     city?: ?string,
     *     gender?: ?string
     * }
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:140'],
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
            'gender' => ['nullable', 'in:male,female'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function directory(ServiceType $serviceType, array $filters): array
    {
        $specialties = Specialty::query()->active()->ordered()->get();
        $governorates = Governorate::query()->active()->ordered()->with([
            'cities' => fn ($query) => $query->active()->ordered(),
        ])->get();

        $place = $filters['_place'] ?? null;
        unset($filters['_place']);

        return [
            'serviceType' => $serviceType,
            'filters' => $filters,
            'specialties' => $specialties,
            'governorates' => $governorates,
            'doctors' => $serviceType->isDoctorLed() ? $this->doctorsFor($serviceType, $filters) : collect(),
            'clinics' => $serviceType->isDoctorLed() ? collect() : $this->clinicsFor($serviceType, $filters),
            'place' => $place,
            'cities' => $this->citiesWithOfferings(
                $serviceType,
                $place instanceof City ? $place->governorate_id : ($place instanceof Governorate ? $place->id : null),
                $place instanceof City ? $place->id : null,
            ),
        ];
    }

    private function cityView(ServiceType $serviceType, City $city): View
    {
        $city->load('governorate');

        return view('services.show', $this->directory($serviceType, [
            'city' => $city->slug,
            '_place' => $city,
        ]));
    }

    private function governorateView(ServiceType $serviceType, Governorate $governorate): View
    {
        return view('services.show', $this->directory($serviceType, [
            'governorate' => $governorate->slug,
            '_place' => $governorate,
        ]));
    }

    /**
     * @return Collection<int, City>
     */
    private function citiesWithOfferings(ServiceType $serviceType, ?int $governorateId = null, ?int $exceptCityId = null)
    {
        return City::query()
            ->active()
            ->ordered()
            ->when($governorateId, fn ($query) => $query->where('governorate_id', $governorateId))
            ->when($exceptCityId, fn ($query) => $query->whereKeyNot($exceptCityId))
            ->where(function (Builder $query) use ($serviceType) {
                if ($serviceType->isDoctorLed()) {
                    $query->whereHas(
                        'addresses.clinic.doctors',
                        fn (Builder $doctors) => $doctors->listable()->offering($serviceType)
                    );

                    return;
                }

                $query->whereHas(
                    'addresses.clinic',
                    fn (Builder $clinics) => $clinics->listable()->offering($serviceType)
                );
            })
            ->limit(8)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Doctor>
     */
    private function doctorsFor(ServiceType $serviceType, array $filters): Collection
    {
        return Doctor::query()
            ->listable()
            ->offering($serviceType)
            ->withRatings()
            ->withCount(['bookings as completed_bookings_count' => fn (Builder $bookings) => $bookings->where('status', BookingStatus::Completed)])
            ->with([
                'specialty',
                'clinics' => fn ($clinics) => $clinics->listable()
                    ->offering($serviceType)
                    ->with(['primaryAddress.city', 'addresses.city']),
            ])
            ->tap(fn (Builder $query) => $this->constrainDoctors($query, $filters))
            ->orderByDesc('completed_bookings_count')
            ->orderBy('name_ar')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Clinic>
     */
    private function clinicsFor(ServiceType $serviceType, array $filters): Collection
    {
        return Clinic::query()
            ->listable()
            ->offering($serviceType)
            ->withRatings()
            ->with([
                'primaryAddress.city',
                'services' => fn ($services) => $services->active()->where('service_type_id', $serviceType->id),
                'doctors' => fn ($doctors) => $this->constrainDoctors(
                    $doctors->listable()->withRatings()->with('specialty')->orderBy('name_ar'),
                    $filters,
                    includeSearch: false,
                ),
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                SearchQuery::constrain($query, $term, function (Builder $inner, string $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%")
                        ->orWhereHas('doctors', function (Builder $doctors) use ($token) {
                            $doctors->listable()->where(function (Builder $names) use ($token) {
                                $names->whereLike('name_ar', "%{$token}%")
                                    ->orWhereLike('name_en', "%{$token}%");
                            });
                        });
                });
            })
            ->when($filters['specialty'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'doctors.specialty',
                fn (Builder $specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['gender'] ?? null, fn (Builder $query, string $gender) => $query->whereHas(
                'doctors',
                fn (Builder $doctors) => $doctors->listable()->where('gender', $gender)
            ))
            ->when($filters['governorate'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'addresses.city.governorate',
                fn (Builder $governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'addresses.city',
                fn (Builder $city) => $city->where('slug', $slug)
            ))
            ->orderBy('name_ar')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function constrainDoctors(Builder $query, array $filters, bool $includeSearch = true): Builder
    {
        return $query
            ->when($includeSearch && ($filters['q'] ?? null), function (Builder $doctors, string $term) {
                SearchQuery::constrain($doctors, $term, function (Builder $inner, string $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%")
                        ->orWhereLike('credentials', "%{$token}%")
                        ->orWhereHas('specialty', fn (Builder $specialty) => $specialty
                            ->whereLike('name_ar', "%{$token}%")
                            ->orWhereLike('name_en', "%{$token}%"));
                });
            })
            ->when($filters['specialty'] ?? null, fn (Builder $doctors, string $slug) => $doctors->whereHas(
                'specialty',
                fn (Builder $specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['gender'] ?? null, fn (Builder $doctors, string $gender) => $doctors->where('gender', $gender))
            ->when($filters['governorate'] ?? null, fn (Builder $doctors, string $slug) => $doctors->whereHas(
                'clinics.addresses.city.governorate',
                fn (Builder $governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn (Builder $doctors, string $slug) => $doctors->whereHas(
                'clinics.addresses.city',
                fn (Builder $city) => $city->where('slug', $slug)
            ));
    }
}
