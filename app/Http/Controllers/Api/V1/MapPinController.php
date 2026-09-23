<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapPinController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
        ]);

        $pins = ClinicAddress::query()
            ->with(['clinic', 'city.governorate'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->whereHas('clinic', fn ($clinics) => $clinics->listable())
            ->when($filters['governorate'] ?? null, fn ($query, string $slug) => $query->whereHas(
                'city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, string $slug) => $query->whereHas(
                'city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (ClinicAddress $address) => [
                'id' => $address->id,
                'name' => $address->clinic?->name,
                'branch' => $address->displayName(),
                'city' => $address->city?->name,
                'lat' => (float) $address->latitude,
                'lng' => (float) $address->longitude,
                'url' => $address->clinic ? route('clinics.show', $address->clinic) : null,
            ]);

        return response()->json(['data' => $pins]);
    }
}
