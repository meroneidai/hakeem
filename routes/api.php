<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Models\Governorate;
use App\Models\ServiceType;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API — example.com/api/v1
|--------------------------------------------------------------------------
| One backend for web, mobile (React Native shell) and the Hermes agent, all
| authenticated with the same Sanctum tokens (md_files/07 §3-4). Booking,
| discovery and medical-record endpoints are added from Phase 3 onward.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('search', SearchController::class)
        ->middleware('throttle:60,1')
        ->name('search');

    Route::get('reference/geography', function () {
        return Governorate::active()->ordered()->with('cities', fn ($q) => $q->active())->get()
            ->map(fn ($governorate) => [
                'id' => $governorate->id,
                'slug' => $governorate->slug,
                'name' => ['ar' => $governorate->name_ar, 'en' => $governorate->name_en],
                'cities' => $governorate->cities->map(fn ($city) => [
                    'id' => $city->id,
                    'slug' => $city->slug,
                    'name' => ['ar' => $city->name_ar, 'en' => $city->name_en],
                ]),
            ]);
    })->name('reference.geography');

    Route::get('reference/specialties', function () {
        return Specialty::active()->ordered()->get()
            ->map(fn ($specialty) => [
                'id' => $specialty->id,
                'slug' => $specialty->slug,
                'category' => $specialty->category,
                'name' => ['ar' => $specialty->name_ar, 'en' => $specialty->name_en],
            ]);
    })->name('reference.specialties');

    Route::get('reference/service-types', function () {
        return ServiceType::active()->ordered()->get()
            ->map(fn ($type) => [
                'id' => $type->id,
                'code' => $type->code,
                'slug' => $type->slug,
                'name' => ['ar' => $type->name_ar, 'en' => $type->name_en],
                'requires' => [
                    'clinic_address' => $type->requires_clinic_address,
                    'patient_address' => $type->requires_patient_address,
                    'time_slot' => $type->requires_time_slot,
                ],
                'is_online' => $type->is_online,
            ]);
    })->name('reference.service-types');

    Route::post('agent/messages', [AgentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('agent.messages');

    Route::middleware('auth:sanctum')->get('me', function (Request $request) {
        return [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'phone' => $request->user()->phone,
            'preferred_language' => $request->user()->preferred_language,
            'roles' => $request->user()->roles->pluck('name'),
        ];
    })->name('me');
});

Route::prefix('agent/v1')
    ->name('api.agent.v1.')
    ->middleware(['hermes'])
    ->group(base_path('routes/agent.php'));
