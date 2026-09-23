<?php

use App\Http\Controllers\Api\V1\AdminOverviewController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ClinicController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\LabOrderController;
use App\Http\Controllers\Api\V1\MapPinController;
use App\Http\Controllers\Api\V1\RecordController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SlotController;
use App\Http\Controllers\Api\V1\StatusController;
use App\Http\Controllers\DeeplinkController;
use App\Models\Governorate;
use App\Models\ServiceType;
use App\Models\Specialty;
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
    Route::get('status', StatusController::class)->name('status');
    Route::get('app-links', [DeeplinkController::class, 'appLinks'])->name('app-links');
    Route::get('map/pins', MapPinController::class)->name('map.pins');
    Route::get('doctors/{doctor:slug}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('doctors/{doctor:slug}/slots', SlotController::class)->name('doctors.slots');
    Route::get('clinics/{clinic:slug}', [ClinicController::class, 'show'])->name('clinics.show');

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

    Route::post('auth/login', [AuthController::class, 'store'])->name('auth.login');
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'show'])->name('me');
        Route::put('me', [AuthController::class, 'update'])->name('me.update');
        Route::post('auth/logout', [AuthController::class, 'destroy'])->name('auth.logout');
        Route::post('me/phone/code', [AuthController::class, 'sendPhoneCode'])->name('me.phone.code');
        Route::post('me/phone/verify', [AuthController::class, 'verifyPhone'])->name('me.phone.verify');
        Route::post('auth/otp', [AuthController::class, 'sendPhoneCode'])->name('auth.otp');
        Route::post('auth/otp/verify', [AuthController::class, 'verifyPhone'])->name('auth.otp.verify');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
        Route::get('records', [RecordController::class, 'index'])->name('records.index');
        Route::get('records/{careDocument}', [RecordController::class, 'show'])->name('records.show');
        Route::get('lab-orders', [LabOrderController::class, 'index'])->name('lab-orders.index');
        Route::get('lab-orders/{labOrder}', [LabOrderController::class, 'show'])->name('lab-orders.show');
        Route::get('admin/overview', AdminOverviewController::class)->name('admin.overview');
    });
});

Route::prefix('agent/v1')
    ->name('api.agent.v1.')
    ->middleware(['hermes'])
    ->group(base_path('routes/agent.php'));
