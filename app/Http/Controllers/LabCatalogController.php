<?php

namespace App\Http\Controllers;

use App\Enums\LabTestCategory;
use App\Models\Governorate;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Support\SearchQuery;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->value() ?: null;
        $maxPriceRaw = $request->input('max_price');
        $maxPrice = is_numeric($maxPriceRaw) && (int) $maxPriceRaw > 0 ? (int) $maxPriceRaw : null;
        $fasting = $request->boolean('fasting');
        $governorate = $request->string('governorate')->trim()->value() ?: null;
        $city = $request->string('city')->trim()->value() ?: null;
        $term = $request->string('q')->trim()->value() ?: null;
        $geoFiltered = filled($governorate) || filled($city);

        $offering = fn (Builder $offerings) => $offerings
            ->forListableLabs($governorate, $city)
            ->with(['clinic.primaryAddress.city']);

        return view('labs.index', [
            'tests' => LabTest::query()
                ->active()
                ->with(['clinicOfferings' => $offering])
                ->when($category, fn ($query) => $query->where('category', $category))
                ->when($term, fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
                }))
                ->when($maxPrice, fn ($query) => $query->where('suggested_price', '<=', $maxPrice))
                ->when($fasting, fn ($query) => $query->where('fasting_hours', '>', 0))
                ->when($geoFiltered, fn ($query) => $query->whereHas(
                    'clinicOfferings',
                    fn ($offerings) => $offerings->forListableLabs($governorate, $city)
                ))
                ->ordered()
                ->get(),
            'packages' => LabPackage::query()
                ->active()
                ->with([
                    'tests',
                    'clinicOfferings' => $offering,
                ])
                ->when($term, fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                    $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
                }))
                ->when($maxPrice, fn ($query) => $query->where('package_price', '<=', $maxPrice))
                ->when($geoFiltered, fn ($query) => $query->whereHas(
                    'clinicOfferings',
                    fn ($offerings) => $offerings->forListableLabs($governorate, $city)
                ))
                ->ordered()
                ->get(),
            'categories' => LabTestCategory::cases(),
            'governorates' => Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get(),
            'activeCategory' => $category,
            'maxPrice' => $maxPrice,
            'fasting' => $fasting,
            'geoFiltered' => $geoFiltered,
        ]);
    }

    public function showTest(LabTest $labTest): View
    {
        abort_unless($labTest->is_active, 404);

        $labTest->load([
            'packages',
            'clinicOfferings' => fn ($offerings) => $offerings->forListableLabs()->with(['clinic.primaryAddress.city']),
        ]);

        return view('labs.show-test', ['test' => $labTest]);
    }

    public function showPackage(LabPackage $labPackage): View
    {
        abort_unless($labPackage->is_active, 404);

        $labPackage->load([
            'tests',
            'clinicOfferings' => fn ($offerings) => $offerings->forListableLabs()->with(['clinic.primaryAddress.city']),
        ]);

        return view('labs.show-package', ['package' => $labPackage]);
    }
}
