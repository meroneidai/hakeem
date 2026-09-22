<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SlotController extends Controller
{
    public function __invoke(Request $request, Doctor $doctor, AvailabilityService $availability): JsonResponse
    {
        $clinics = $doctor->clinics();
        $visible = method_exists($clinics->getModel(), 'scopeListable')
            ? $clinics->listable()->exists()
            : $clinics->where('is_active', true)->exists();

        abort_unless($doctor->is_active && $visible, 404);

        $validated = $request->validate([
            'clinic_address_id' => ['required', 'integer', 'exists:clinic_addresses,id'],
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'date' => ['required', 'date'],
        ]);

        $clinicIds = method_exists($doctor->clinics()->getModel(), 'scopeListable')
            ? $doctor->clinics()->listable()->pluck('clinics.id')
            : $doctor->clinics()->where('is_active', true)->pluck('clinics.id');

        $address = ClinicAddress::query()
            ->with('schedules')
            ->whereKey($validated['clinic_address_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->first();

        abort_unless($address?->is_active, 404);

        $serviceType = ServiceType::query()->findOrFail($validated['service_type_id']);
        $doctor->load('availability');

        return response()->json([
            'data' => $availability->slots(
                $doctor,
                $address,
                $serviceType,
                Carbon::parse($validated['date'])->startOfDay(),
            ),
            'duration_minutes' => $availability->durationMinutes($address, $serviceType),
        ]);
    }
}
