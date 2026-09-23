<?php

namespace App\Support;

use App\Models\City;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Models\MedicalArticle;
use App\Models\Promotion;
use App\Models\SeoPage;
use App\Models\ServiceType;
use App\Models\SitePage;
use App\Models\Specialty;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class SitemapBuilder
{
    /**
     * Live public URLs: static named routes, generated SEO pages, and current catalog rows.
     *
     * @return Collection<int, array{loc: string, lastmod?: string|null, changefreq: string, priority: string}>
     */
    public function urls(): Collection
    {
        $urls = collect();
        $sitePages = SitePage::query()->get()->keyBy('path');

        foreach ($this->staticRoutes() as $name => $meta) {
            if (! Route::has($name)) {
                continue;
            }

            $loc = route($name);
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            $path = '/'.ltrim($path, '/');
            $path = $path === '/' || $path === '' ? '/' : $path;
            $sitePage = $sitePages->get($path);

            if ($sitePage && (! $sitePage->is_published || ! $sitePage->is_indexable)) {
                continue;
            }

            $urls->push([
                'loc' => $loc,
                'changefreq' => $meta['changefreq'],
                'priority' => $meta['priority'],
            ]);
        }

        $sitePages
            ->filter(fn (SitePage $page) => ! $page->is_system && $page->is_published && $page->is_indexable)
            ->each(fn (SitePage $page) => $urls->push($this->entry($page->publicUrl(), $page->updated_at?->toAtomString(), number_format($page->sitemap_priority / 10, 1))));

        SeoPage::query()
            ->indexable()
            ->orderByDesc('sitemap_priority')
            ->get(['path', 'sitemap_priority', 'updated_at'])
            ->each(function (SeoPage $page) use ($urls): void {
                $urls->push([
                    'loc' => url($page->path),
                    'lastmod' => $page->updated_at?->toAtomString(),
                    'priority' => number_format($page->sitemap_priority / 10, 1),
                    'changefreq' => 'weekly',
                ]);
            });

        Doctor::query()->listable()->orderBy('id')->get(['slug', 'updated_at'])
            ->each(fn (Doctor $doctor) => $urls->push($this->entry(route('doctors.show', $doctor), $doctor->updated_at?->toAtomString(), '0.8')));

        Clinic::query()->listable()->orderBy('id')->get(['slug', 'updated_at'])
            ->each(fn (Clinic $clinic) => $urls->push($this->entry(route('clinics.show', $clinic), $clinic->updated_at?->toAtomString(), '0.8')));

        Specialty::query()->active()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (Specialty $specialty) => $urls->push($this->entry(route('specialties.show', $specialty), $specialty->updated_at?->toAtomString(), '0.7')));

        ServiceType::query()->active()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (ServiceType $type) => $urls->push($this->entry(route('services.show', $type), $type->updated_at?->toAtomString(), '0.7')));

        City::query()->active()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (City $city) => $urls->push($this->entry(route('cities.show', $city), $city->updated_at?->toAtomString(), '0.6')));

        Promotion::query()->running()->orderBy('id')->get(['slug', 'updated_at'])
            ->each(fn (Promotion $offer) => $urls->push($this->entry(route('offers.show', $offer), $offer->updated_at?->toAtomString(), '0.6', 'daily')));

        LabTest::query()->active()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (LabTest $test) => $urls->push($this->entry(route('labs.tests.show', $test), $test->updated_at?->toAtomString(), '0.5', 'monthly')));

        LabPackage::query()->active()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (LabPackage $package) => $urls->push($this->entry(route('labs.packages.show', $package), $package->updated_at?->toAtomString(), '0.5', 'monthly')));

        MedicalArticle::query()->published()->ordered()->get(['slug', 'updated_at'])
            ->each(fn (MedicalArticle $article) => $urls->push($this->entry(route('library.show', $article), $article->updated_at?->toAtomString(), '0.5', 'monthly')));

        $this->specialtyLocations($urls);
        $this->serviceLocations($urls);

        return $urls->unique('loc')->values();
    }

    /**
     * @return array<string, array{changefreq: string, priority: string}>
     */
    private function staticRoutes(): array
    {
        return [
            'home' => ['changefreq' => 'daily', 'priority' => '1.0'],
            'doctors.index' => ['changefreq' => 'daily', 'priority' => '0.9'],
            'clinics.index' => ['changefreq' => 'daily', 'priority' => '0.9'],
            'specialties.index' => ['changefreq' => 'weekly', 'priority' => '0.8'],
            'services.index' => ['changefreq' => 'weekly', 'priority' => '0.8'],
            'offers.index' => ['changefreq' => 'daily', 'priority' => '0.8'],
            'labs.index' => ['changefreq' => 'weekly', 'priority' => '0.8'],
            'cities.index' => ['changefreq' => 'weekly', 'priority' => '0.7'],
            'home-care' => ['changefreq' => 'monthly', 'priority' => '0.6'],
            'teleconsultation' => ['changefreq' => 'monthly', 'priority' => '0.6'],
            'how-it-works' => ['changefreq' => 'monthly', 'priority' => '0.5'],
            'about' => ['changefreq' => 'monthly', 'priority' => '0.5'],
            'contact' => ['changefreq' => 'monthly', 'priority' => '0.5'],
            'help' => ['changefreq' => 'monthly', 'priority' => '0.4'],
            'library.index' => ['changefreq' => 'weekly', 'priority' => '0.6'],
            'accessibility' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            'terms' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            'privacy' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            'cookies' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            'cancellation' => ['changefreq' => 'yearly', 'priority' => '0.3'],
            'disclaimer' => ['changefreq' => 'yearly', 'priority' => '0.3'],
        ];
    }

    /**
     * @param  Collection<int, array{loc: string, lastmod?: string|null, changefreq: string, priority: string}>  $urls
     */
    private function specialtyLocations(Collection $urls): void
    {
        Specialty::query()->active()->ordered()->get()->each(function (Specialty $specialty) use ($urls): void {
            City::query()
                ->active()
                ->whereHas(
                    'addresses.clinic.doctors',
                    fn ($doctors) => $doctors->active()->where('specialty_id', $specialty->id)
                )
                ->ordered()
                ->get(['slug', 'updated_at'])
                ->each(fn (City $city) => $urls->push($this->entry(
                    route('specialties.location', [$specialty, $city->slug]),
                    $city->updated_at?->toAtomString(),
                    '0.6',
                )));
        });
    }

    /**
     * @param  Collection<int, array{loc: string, lastmod?: string|null, changefreq: string, priority: string}>  $urls
     */
    private function serviceLocations(Collection $urls): void
    {
        ServiceType::query()->active()->ordered()->get()->each(function (ServiceType $type) use ($urls): void {
            City::query()
                ->active()
                ->whereHas(
                    'addresses.clinic',
                    fn ($clinics) => $clinics->listable()->offering($type)
                )
                ->ordered()
                ->get(['slug', 'updated_at'])
                ->each(fn (City $city) => $urls->push($this->entry(
                    route('services.location', [$type, $city->slug]),
                    $city->updated_at?->toAtomString(),
                    '0.6',
                )));
        });
    }

    /**
     * @return array{loc: string, lastmod: string|null, changefreq: string, priority: string}
     */
    private function entry(string $loc, ?string $lastmod, string $priority, string $changefreq = 'weekly'): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
