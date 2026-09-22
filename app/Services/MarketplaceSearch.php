<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Support\PublicImage;
use App\Support\SearchQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class MarketplaceSearch
{
    /**
     * @param  array{
     *     q?: ?string,
     *     type?: ?string,
     *     specialty?: ?string,
     *     governorate?: ?string,
     *     city?: ?string,
     *     gender?: ?string,
     *     min_experience?: int|string|null,
     *     category?: ?string,
     *     max_price?: int|string|null,
     *     limit?: int
     * }  $filters
     * @return array{
     *     doctors: Collection<int, Doctor>,
     *     clinics: Collection<int, Clinic>,
     *     services: Collection<int, ServiceType>,
     *     offers: Collection<int, Promotion>,
     *     labs: Collection<int, LabTest>,
     *     packages: Collection<int, LabPackage>,
     *     specialties: Collection<int, Specialty>
     * }
     */
    public function query(array $filters): array
    {
        $term = trim((string) ($filters['q'] ?? ''));
        $type = $filters['type'] ?? 'all';
        $limit = max(1, min(24, (int) ($filters['limit'] ?? 8)));

        $doctors = collect();
        $clinics = collect();
        $services = collect();
        $offers = collect();
        $labs = collect();
        $packages = collect();
        $specialties = collect();

        if (in_array($type, ['all', 'doctors', 'services'], true)) {
            $doctors = $this->doctors($filters, $term, $limit);
        }

        if (in_array($type, ['all', 'clinics'], true)) {
            $clinics = $this->clinics($filters, $term, $limit);
        }

        if (in_array($type, ['all', 'services'], true)) {
            $services = $this->services($term, $limit);
        }

        if (in_array($type, ['all', 'offers'], true)) {
            $offers = $this->offers($filters, $term, $limit);
        }

        if (in_array($type, ['all', 'labs'], true)) {
            $labs = $this->labs($filters, $term, $limit);
            $packages = $this->packages($filters, $term, $limit);
        }

        if ($term !== '' && in_array($type, ['all', 'doctors', 'services'], true)) {
            $specialties = Specialty::query()
                ->active()
                ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%");
                }))
                ->ordered()
                ->limit(6)
                ->get();
        }

        return compact('doctors', 'clinics', 'services', 'offers', 'labs', 'packages', 'specialties');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hasActiveFilters(array $filters): bool
    {
        foreach (['q', 'specialty', 'governorate', 'city', 'gender', 'min_experience', 'category', 'max_price'] as $key) {
            if (filled($filters[$key] ?? null)) {
                return true;
            }
        }

        return ($filters['type'] ?? 'all') !== 'all';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function toPayload(array $filters): array
    {
        $results = $this->query($filters);

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'type' => $filters['type'] ?? 'all',
            'counts' => [
                'doctors' => $results['doctors']->count(),
                'clinics' => $results['clinics']->count(),
                'services' => $results['services']->count(),
                'offers' => $results['offers']->count(),
                'labs' => $results['labs']->count() + $results['packages']->count(),
            ],
            'doctors' => $results['doctors']->map(fn (Doctor $doctor) => $this->doctorCard($doctor))->values()->all(),
            'clinics' => $results['clinics']->map(fn (Clinic $clinic) => $this->clinicCard($clinic))->values()->all(),
            'services' => $results['services']->map(fn (ServiceType $type) => $this->serviceCard($type))->values()->all(),
            'offers' => $results['offers']->map(fn (Promotion $offer) => $this->offerCard($offer))->values()->all(),
            'labs' => $results['labs']->map(fn (LabTest $test) => $this->labCard($test))->values()->all(),
            'packages' => $results['packages']->map(fn (LabPackage $package) => $this->packageCard($package))->values()->all(),
            'specialties' => $results['specialties']->map(fn (Specialty $specialty) => [
                'name' => $specialty->name,
                'slug' => $specialty->slug,
                'url' => $this->namedUrl('specialties.show', '/specialties/'.$specialty->slug, $specialty),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Doctor>
     */
    private function doctors(array $filters, string $term, int $limit): Collection
    {
        return Doctor::query()
            ->when(
                method_exists(Doctor::class, 'scopeListable'),
                fn ($query) => $query->listable(),
                fn ($query) => $query->active(),
            )
            ->when(method_exists(Doctor::class, 'scopeWithRatings'), fn ($query) => $query->withRatings())
            ->when(method_exists(Doctor::class, 'bookings'), fn ($query) => $query->withCount([
                'bookings as completed_bookings_count' => fn ($bookings) => $bookings->where('status', BookingStatus::Completed),
            ]))
            ->with(['specialty', 'clinics' => fn ($clinics) => $clinics
                ->when(method_exists(Clinic::class, 'scopeListable'), fn ($visible) => $visible->listable())
                ->with('addresses.city')])
            ->when($term !== '', function ($query) use ($term) {
                SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")
                        ->orWhereLike('name_en', "%{$token}%")
                        ->orWhereLike('credentials', "%{$token}%")
                        ->orWhereHas('specialty', fn ($specialty) => $specialty
                            ->whereLike('name_ar', "%{$token}%")
                            ->orWhereLike('name_en', "%{$token}%"));
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
            ->when(isset($filters['min_experience']) && $filters['min_experience'] !== '' && $filters['min_experience'] !== null, fn ($query) => $query->where('years_of_experience', '>=', (int) $filters['min_experience']))
            ->orderBy('name_ar')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Clinic>
     */
    private function clinics(array $filters, string $term, int $limit): Collection
    {
        return Clinic::query()
            ->listable()
            ->withRatings()
            ->with(['primaryAddress.city', 'doctors.specialty', 'services' => fn ($services) => $services->active()])
            ->withCount('doctors')
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")
                    ->orWhereLike('name_en', "%{$token}%");
            }))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when($filters['specialty'] ?? null, fn ($query, $slug) => $query->whereHas(
                'doctors.specialty',
                fn ($specialty) => $specialty->where('slug', $slug)
            ))
            ->orderBy('name_ar')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ServiceType>
     */
    private function services(string $term, int $limit): Collection
    {
        return ServiceType::query()
            ->active()
            ->with(['clinicServices' => fn ($services) => $services->active()->whereHas('clinic', fn ($clinic) => $clinic->listable())])
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")
                    ->orWhereLike('name_en', "%{$token}%")
                    ->orWhereLike('description_ar', "%{$token}%")
                    ->orWhereLike('description_en', "%{$token}%");
            }))
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Promotion>
     */
    private function offers(array $filters, string $term, int $limit): Collection
    {
        return Promotion::query()
            ->running()
            ->with(['clinic' => fn ($clinic) => $clinic->withRatings()->with('primaryAddress.city'), 'specialty'])
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('title_ar', "%{$token}%")
                    ->orWhereLike('title_en', "%{$token}%")
                    ->orWhereLike('includes_ar', "%{$token}%")
                    ->orWhereLike('includes_en', "%{$token}%");
            }))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['specialty'] ?? null, fn ($query, $slug) => $query->whereHas(
                'specialty',
                fn ($specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when(isset($filters['max_price']) && $filters['max_price'] !== '' && $filters['max_price'] !== null, fn ($query) => $query->where('offer_price', '<=', (int) $filters['max_price']))
            ->latest('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LabTest>
     */
    private function labs(array $filters, string $term, int $limit): Collection
    {
        return LabTest::query()
            ->active()
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")
                    ->orWhereLike('name_en', "%{$token}%")
                    ->orWhereLike('measures_ar', "%{$token}%")
                    ->orWhereLike('measures_en', "%{$token}%");
            }))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinicOfferings.clinic.addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinicOfferings.clinic.addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when(isset($filters['max_price']) && $filters['max_price'] !== '' && $filters['max_price'] !== null, fn ($query) => $query->where('suggested_price', '<=', (int) $filters['max_price']))
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LabPackage>
     */
    private function packages(array $filters, string $term, int $limit): Collection
    {
        return LabPackage::query()
            ->active()
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")
                    ->orWhereLike('name_en', "%{$token}%");
            }))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinicOfferings.clinic.addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinicOfferings.clinic.addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when(isset($filters['max_price']) && $filters['max_price'] !== '' && $filters['max_price'] !== null, fn ($query) => $query->where('package_price', '<=', (int) $filters['max_price']))
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function doctorCard(Doctor $doctor): array
    {
        $rating = $this->ratings($doctor);

        return [
            'name' => $doctor->name,
            'slug' => $doctor->slug,
            'url' => $this->namedUrl('doctors.show', '/doctors/'.$doctor->slug, $doctor),
            'book_url' => $this->namedUrl('book.doctors.create', '/book/doctors/'.$doctor->slug, $doctor),
            'specialty' => $doctor->specialty?->name,
            'years' => $doctor->years_of_experience,
            'fee' => $doctor->consultation_fee !== null ? (float) $doctor->consultation_fee : null,
            'fee_label' => $doctor->consultation_fee !== null
                ? number_format((float) $doctor->consultation_fee).' '.__('common.currency')
                : null,
            'gender' => $doctor->gender,
            'credentials' => $doctor->credentials,
            'photo' => PublicImage::url($doctor->profile_photo_path),
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
            'clinics' => $doctor->clinics->pluck('name')->filter()->values()->all(),
            'visits' => (int) ($doctor->completed_bookings_count ?? 0),
            'visits_label' => __('discover.doctors.visits', ['count' => (int) ($doctor->completed_bookings_count ?? 0)]),
            'cities' => $doctor->clinics
                ->flatMap->addresses
                ->pluck('city.name')
                ->unique()
                ->filter()
                ->take(2)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clinicCard(Clinic $clinic): array
    {
        $rating = $this->ratings($clinic);
        $fromPrice = $clinic->services
            ->filter(fn ($service) => $service->is_active)
            ->map(fn ($service) => $service->effectivePrice())
            ->filter(fn ($price) => $price > 0)
            ->min();

        return [
            'name' => $clinic->name,
            'slug' => $clinic->slug,
            'url' => $this->namedUrl('clinics.show', '/clinics/'.$clinic->slug, $clinic),
            'city' => $clinic->primaryAddress?->city?->name,
            'doctors_count' => $clinic->doctors_count ?? $clinic->doctors->count(),
            'logo' => PublicImage::url($clinic->logo_path),
            'from_price' => $fromPrice ? (float) $fromPrice : null,
            'from_price_label' => $fromPrice
                ? number_format((float) $fromPrice).' '.__('common.currency')
                : null,
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
            'verified' => $clinic->isVerified(),
            'lat' => $clinic->primaryAddress?->latitude !== null ? (float) $clinic->primaryAddress->latitude : null,
            'lng' => $clinic->primaryAddress?->longitude !== null ? (float) $clinic->primaryAddress->longitude : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceCard(ServiceType $type): array
    {
        $fromPrice = $type->clinicServices
            ->map(fn ($service) => $service->effectivePrice())
            ->filter(fn ($price) => $price > 0)
            ->min();

        return [
            'name' => $type->name,
            'slug' => $type->slug,
            'url' => $this->namedUrl('services.show', '/services/'.$type->slug, $type),
            'description' => $type->description,
            'image' => PublicImage::url($type->image_path),
            'icon' => method_exists($type, 'uiIcon') ? $type->uiIcon() : 'stethoscope',
            'from_price' => $fromPrice ? (float) $fromPrice : null,
            'from_price_label' => $fromPrice
                ? number_format((float) $fromPrice).' '.__('common.currency')
                : null,
            'rating_average' => null,
            'rating_count' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function offerCard(Promotion $offer): array
    {
        $rating = $this->ratings($offer->clinic);

        return [
            'title' => $offer->title,
            'slug' => $offer->slug,
            'url' => $this->namedUrl('offers.show', '/offers/'.$offer->slug, $offer),
            'offer_price' => $offer->offer_price !== null ? (float) $offer->offer_price : null,
            'original_price' => $offer->original_price !== null ? (float) $offer->original_price : null,
            'price_label' => $offer->offer_price !== null
                ? number_format((float) $offer->offer_price).' '.__('common.currency')
                : null,
            'image' => PublicImage::url($offer->banner_image_path),
            'category' => $offer->category?->label(),
            'clinic' => $offer->clinic?->name,
            'city' => $offer->clinic?->primaryAddress?->city?->name,
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function labCard(LabTest $test): array
    {
        return [
            'name' => $test->name,
            'slug' => $test->slug,
            'url' => $this->namedUrl('labs.tests.show', '/labs/tests/'.$test->slug, $test),
            'price' => $test->suggested_price !== null ? (float) $test->suggested_price : null,
            'price_label' => $test->suggested_price !== null
                ? number_format((float) $test->suggested_price).' '.__('common.currency')
                : null,
            'category' => $test->category->label(),
            'fasting' => $test->fasting_hours
                ? __('labs.fasting', ['hours' => $test->fasting_hours])
                : __('labs.no_fasting'),
            'image' => $test->imageUrl(),
            'icon' => 'beaker',
            'rating_average' => null,
            'rating_count' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function packageCard(LabPackage $package): array
    {
        return [
            'name' => $package->name,
            'slug' => $package->slug,
            'url' => $this->namedUrl('labs.packages.show', '/labs/packages/'.$package->slug, $package),
            'price' => $package->package_price !== null ? (float) $package->package_price : null,
            'original_price' => $package->original_price !== null ? (float) $package->original_price : null,
            'price_label' => $package->package_price !== null
                ? number_format((float) $package->package_price).' '.__('common.currency')
                : null,
            'image' => PublicImage::url($package->image_path),
            'icon' => 'beaker',
            'rating_average' => null,
            'rating_count' => 0,
        ];
    }

    /**
     * @return array{average: ?float, count: int}
     */
    private function ratings(?object $rateable): array
    {
        if ($rateable && method_exists($rateable, 'ratingSummary')) {
            return $rateable->ratingSummary();
        }

        return ['average' => null, 'count' => 0];
    }

    private function namedUrl(string $name, string $fallback, mixed $parameters = []): string
    {
        return Route::has($name) ? route($name, $parameters) : url($fallback);
    }
}
