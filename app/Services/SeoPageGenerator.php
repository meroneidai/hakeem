<?php

namespace App\Services;

use App\Models\City;
use App\Models\Governorate;
use App\Models\SeoPage;
use App\Models\ServiceType;
use App\Models\Specialty;
use Illuminate\Support\Collection;

/**
 * Builds the programmatic SEO surface described in md_files/06 §3.1
 * and hakeem-all-ui-interfaces (prefixed /specialties, /services, /cities paths).
 * Idempotent — only missing paths are added.
 */
class SeoPageGenerator
{
    /** @var array<string, true> */
    private array $takenPaths = [];

    /** @var array<string, true> */
    private array $existingPages = [];

    public function generateMissing(): int
    {
        $this->takenPaths = SeoPage::pluck('path')->mapWithKeys(fn ($path) => [$path => true])->all();

        // Identity is the page's type plus what it points at — not its path — so a
        // path collision resolved by a fallback never produces a duplicate page.
        $this->existingPages = SeoPage::get(['page_type', 'governorate_id', 'city_id', 'specialty_id', 'service_type_id'])
            ->mapWithKeys(fn (SeoPage $page) => [$this->signature(
                $page->page_type,
                [
                    'governorate_id' => $page->governorate_id,
                    'city_id' => $page->city_id,
                    'specialty_id' => $page->specialty_id,
                    'service_type_id' => $page->service_type_id,
                ],
            ) => true])
            ->all();

        $governorates = Governorate::active()->ordered()->get();
        $cities = City::active()->with('governorate')->ordered()->get();
        $specialties = Specialty::active()->ordered()->get();
        $serviceTypes = ServiceType::active()->ordered()->get();

        $rows = collect()
            ->merge($this->governoratePages($governorates))
            ->merge($this->specialtyPages($specialties))
            ->merge($this->servicePages($serviceTypes))
            ->merge($this->cityPages($cities))
            ->merge($this->governorateSpecialtyPages($governorates, $specialties))
            ->merge($this->citySpecialtyPages($cities, $specialties));

        $rows->chunk(500)->each(fn (Collection $chunk) => SeoPage::insert($chunk->all()));

        return $rows->count();
    }

    private function governoratePages(Collection $governorates): Collection
    {
        return $governorates->map(fn (Governorate $governorate) => $this->row(
            'governorate',
            "/cities/{$governorate->slug}",
            ['governorate_id' => $governorate->id],
            priority: 7,
        ))->filter()->values();
    }

    private function cityPages(Collection $cities): Collection
    {
        return $cities->map(fn (City $city) => $this->row(
            'city',
            "/cities/{$city->slug}",
            ['city_id' => $city->id, 'governorate_id' => $city->governorate_id],
            fallbackPath: "/cities/{$city->governorate?->slug}/{$city->slug}",
            priority: 6,
        ))->filter()->values();
    }

    private function specialtyPages(Collection $specialties): Collection
    {
        return $specialties->map(fn (Specialty $specialty) => $this->row(
            'specialty',
            "/specialties/{$specialty->slug}",
            ['specialty_id' => $specialty->id],
            priority: 7,
        ))->filter()->values();
    }

    private function servicePages(Collection $serviceTypes): Collection
    {
        return $serviceTypes->map(fn (ServiceType $serviceType) => $this->row(
            'service',
            "/services/{$serviceType->slug}",
            ['service_type_id' => $serviceType->id],
            priority: 8,
        ))->filter()->values();
    }

    private function governorateSpecialtyPages(Collection $governorates, Collection $specialties): Collection
    {
        return $governorates->crossJoin($specialties)->map(
            fn (array $pair) => $this->row(
                'governorate_specialty',
                "/specialties/{$pair[1]->slug}/{$pair[0]->slug}",
                ['governorate_id' => $pair[0]->id, 'specialty_id' => $pair[1]->id],
                priority: 8,
            )
        )->filter()->values();
    }

    /**
     * The highest-value long-tail page type ("Dentists in Nasr City").
     */
    private function citySpecialtyPages(Collection $cities, Collection $specialties): Collection
    {
        return $cities->crossJoin($specialties)->map(
            fn (array $pair) => $this->row(
                'city_specialty',
                "/specialties/{$pair[1]->slug}/{$pair[0]->slug}",
                [
                    'city_id' => $pair[0]->id,
                    'governorate_id' => $pair[0]->governorate_id,
                    'specialty_id' => $pair[1]->id,
                ],
                fallbackPath: "/specialties/{$pair[1]->slug}/{$pair[0]->governorate?->slug}/{$pair[0]->slug}",
                priority: 10,
            )
        )->filter()->values();
    }

    private function row(string $type, string $path, array $relations, ?string $fallbackPath = null, int $priority = 5): ?array
    {
        $signature = $this->signature($type, $relations);

        if (isset($this->existingPages[$signature])) {
            return null;
        }

        if (isset($this->takenPaths[$path])) {
            if ($fallbackPath === null || isset($this->takenPaths[$fallbackPath])) {
                return null;
            }

            $path = $fallbackPath;
        }

        $this->takenPaths[$path] = true;
        $this->existingPages[$signature] = true;

        return [
            'page_type' => $type,
            'path' => $path,
            'governorate_id' => $relations['governorate_id'] ?? null,
            'city_id' => $relations['city_id'] ?? null,
            'specialty_id' => $relations['specialty_id'] ?? null,
            'service_type_id' => $relations['service_type_id'] ?? null,
            'is_indexable' => true,
            'sitemap_priority' => $priority,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function signature(string $type, array $relations): string
    {
        return implode('|', [
            $type,
            $relations['governorate_id'] ?? '',
            $relations['city_id'] ?? '',
            $relations['specialty_id'] ?? '',
            $relations['service_type_id'] ?? '',
        ]);
    }
}
