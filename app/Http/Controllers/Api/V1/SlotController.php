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
        $clinicIds = method_exists($doctor->clinics()->getModel(), 'scopeListable')
            ? $doctor->clinics()->listable()->pluck('clinics.id')
            : $doctor->clinics()->where('is_active', true)->pluck('clinics.id');

        abort_unless($doctor->is_active && $clinicIds->isNotEmpty(), 404);

        $validated = $request->validate([
            'clinic_address_id' => ['nullable', 'integer', 'exists:clinic_addresses,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'date' => ['nullable', 'date'],
        ]);

        $address = ClinicAddress::query()
            ->with('schedules')
            ->whereIn('clinic_id', $clinicIds)
            ->when($validated['clinic_address_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        abort_unless($address, 404);

        $serviceType = ServiceType::query()
            ->when(
                $validated['service_type_id'] ?? null,
                fn ($query, $id) => $query->whereKey($id),
                fn ($query) => $query->where('code', 'clinic_appointment'),
            )
            ->first()
            ?? ServiceType::query()->where('is_active', true)->orderBy('display_order')->first();

        abort_unless($serviceType, 404);

        $date = $validated['date'] ?? now('Africa/Cairo')->toDateString();
        $doctor->load('availability');

        return response()->json([
            'options' => [
                'addresses' => ClinicAddress::query()
                    ->with('city')
                    ->whereIn('clinic_id', $clinicIds)
                    ->where('is_active', true)
                    ->orderByDesc('is_primary')
                    ->orderBy('id')
                    ->get()
                    ->map(fn (ClinicAddress $item) => [
                        'id' => $item->id,
                        'label' => $item->label_ar ?: $item->address_line,
                        'city' => $item->city?->name_ar ?? $item->city?->name,
                        'primary' => (bool) $item->is_primary,
                    ])->values(),
                'service_types' => ServiceType::query()
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get()
                    ->map(fn (ServiceType $item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name_ar,
                        'requires_patient_address' => (bool) $item->requires_patient_address,
                    ])->values(),
            ],
            'resolved' => [
                'doctor_id' => $doctor->id,
                'clinic_address_id' => $address->id,
                'service_type_id' => $serviceType->id,
                'date' => $date,
            ],
            'data' => $availability->slots(
                $doctor,
                $address,
                $serviceType,
                Carbon::parse($date)->startOfDay(),
            ),
            'duration_minutes' => $availability->durationMinutes($address, $serviceType),
        ]);
    }
}
