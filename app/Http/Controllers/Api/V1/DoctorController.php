<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Support\PublicImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

class DoctorController extends Controller
{
    public function show(Doctor $doctor): JsonResponse
    {
        abort_unless($doctor->is_active && $this->doctorHasVisibleClinic($doctor), 404);

        $doctor->load([
            'specialty',
            'clinics' => fn ($clinics) => $clinics->listable()->with([
                'primaryAddress.city',
                'addresses' => fn ($addresses) => $addresses->active()->with('city'),
                'services' => fn ($services) => $services->active()->with('serviceType'),
            ]),
        ]);

        $rating = method_exists($doctor, 'ratingSummary')
            ? $doctor->ratingSummary()
            : ['average' => null, 'count' => 0];

        return response()->json([
            'id' => $doctor->id,
            'name' => $doctor->name,
            'slug' => $doctor->slug,
            'url' => $this->namedUrl('doctors.show', '/doctors/'.$doctor->slug, $doctor),
            'book_url' => $this->namedUrl('book.doctors.create', '/book/doctors/'.$doctor->slug, $doctor),
            'slots_url' => $this->namedUrl('api.agent.v1.doctors.slots', '/api/agent/v1/doctors/'.$doctor->slug.'/slots', $doctor),
            'specialty' => $doctor->specialty?->name,
            'bio' => $doctor->translated('bio'),
            'years' => $doctor->years_of_experience,
            'gender' => $doctor->gender,
            'credentials' => $doctor->credentials,
            'fee' => $doctor->consultation_fee !== null ? (float) $doctor->consultation_fee : null,
            'photo' => PublicImage::url($doctor->profile_photo_path),
            'rating_average' => $rating['average'],
            'rating_count' => $rating['count'],
            'clinics' => $doctor->clinics->map(fn ($clinic) => [
                'name' => $clinic->name,
                'slug' => $clinic->slug,
                'url' => $this->namedUrl('clinics.show', '/clinics/'.$clinic->slug, $clinic),
                'addresses' => $clinic->addresses->map(fn (ClinicAddress $address) => $this->addressPin($address))->values()->all(),
            ])->values()->all(),
        ]);
    }

    private function doctorHasVisibleClinic(Doctor $doctor): bool
    {
        $clinics = $doctor->clinics();

        if (method_exists($clinics->getModel(), 'scopeListable')) {
            return $clinics->listable()->exists();
        }

        return $clinics->where('is_active', true)->exists();
    }

    private function namedUrl(string $name, string $fallback, mixed $parameters = []): string
    {
        return Route::has($name) ? route($name, $parameters) : url($fallback);
    }

    /**
     * @return array<string, mixed>
     */
    private function addressPin(ClinicAddress $address): array
    {
        return [
            'id' => $address->id,
            'name' => $address->displayName(),
            'city' => $address->city?->name,
            'lat' => $address->latitude !== null ? (float) $address->latitude : null,
            'lng' => $address->longitude !== null ? (float) $address->longitude : null,
        ];
    }
}
