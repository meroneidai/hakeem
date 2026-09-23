<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Support\PublicImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

class ClinicController extends Controller
{
    public function show(Clinic $clinic): JsonResponse
    {
        abort_unless($clinic->is_active && $clinic->isVerified(), 404);

        $clinic->load([
            'primaryAddress.city',
            'addresses' => fn ($addresses) => $addresses->active()->with('city'),
            'doctors' => fn ($doctors) => $doctors->active()->with('specialty'),
            'services' => fn ($services) => $services->active()->with('serviceType'),
        ]);

        $rating = method_exists($clinic, 'ratingSummary')
            ? $clinic->ratingSummary()
            : ['average' => null, 'count' => 0];

        return response()->json([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'slug' => $clinic->slug,
            'url' => $this->namedUrl('clinics.show', '/clinics/'.$clinic->slug, $clinic),
            'description' => $clinic->translated('description'),
            'logo' => PublicImage::url($clinic->logo_path),
            'phone' => $clinic->phone,
            'verified' => $clinic->isVerified(),
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
            'city' => $clinic->primaryAddress?->city?->name,
            'addresses' => $clinic->addresses->map(fn (ClinicAddress $address) => [
                'id' => $address->id,
                'name' => $address->displayName(),
                'city' => $address->city?->name,
                'lat' => $address->latitude !== null ? (float) $address->latitude : null,
                'lng' => $address->longitude !== null ? (float) $address->longitude : null,
                'maps_url' => method_exists($address, 'mapsUrl') ? $address->mapsUrl() : null,
            ])->values()->all(),
            'doctors' => $clinic->doctors->map(fn ($doctor) => [
                'name' => $doctor->name,
                'slug' => $doctor->slug,
                'url' => $this->namedUrl('doctors.show', '/doctors/'.$doctor->slug, $doctor),
                'specialty' => $doctor->specialty?->name,
            ])->values()->all(),
            'services' => $clinic->services->map(fn ($service) => [
                'name' => $service->serviceType?->name,
                'slug' => $service->serviceType?->slug,
                'price' => method_exists($service, 'effectivePrice') ? $service->effectivePrice() : $service->price,
            ])->values()->all(),
        ]);
    }

    private function namedUrl(string $name, string $fallback, mixed $parameters = []): string
    {
        return Route::has($name) ? route($name, $parameters) : url($fallback);
    }
}
