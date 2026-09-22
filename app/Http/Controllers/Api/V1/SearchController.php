<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, MarketplaceSearch $search): JsonResponse
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
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        return response()->json($search->toPayload($filters), 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
