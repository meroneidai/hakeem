<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Specialty;
use App\Services\MarketplaceSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Live filtering swaps the rendered results fragment so every surface
     * keeps the same server-rendered cards.
     */
    public function __invoke(Request $request, MarketplaceSearch $search): View|string
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:all,doctors,clinics,services,offers,labs'],
            'specialty' => ['nullable', 'string', 'max:140'],
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
            'gender' => ['nullable', 'in:male,female'],
            'min_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'category' => ['nullable', 'string', 'max:64'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $filters['limit'] = 12;
        $results = $search->query($filters);

        return view('search.index', [
            'filters' => $filters,
            'term' => trim((string) ($filters['q'] ?? '')),
            'type' => $filters['type'] ?? 'all',
            'hasFilters' => $search->hasActiveFilters($filters),
            'doctors' => $results['doctors'],
            'clinics' => $results['clinics'],
            'services' => $results['services'],
            'offers' => $results['offers'],
            'labs' => $results['labs'],
            'packages' => $results['packages'],
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'governorates' => Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get(),
        ])->fragmentIf($request->hasHeader('X-Search-Fragment'), 'results');
    }
}
