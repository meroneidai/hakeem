<?php

use App\Http\Controllers\Api\Agent\V1\CustomerController;
use App\Http\Controllers\Api\Agent\V1\DiscoveryController;
use App\Http\Controllers\Api\Agent\V1\ManifestController;
use App\Http\Controllers\Api\Agent\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\ClinicController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SlotController;
use Illuminate\Support\Facades\Route;

Route::get('/', ManifestController::class)->name('manifest');
Route::get('help', [DiscoveryController::class, 'help'])->name('help');
Route::get('suggestions', [DiscoveryController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('suggestions');
Route::get('search', SearchController::class)
    ->middleware('throttle:60,1')
    ->name('search');
Route::get('campaigns', [DiscoveryController::class, 'campaigns'])->name('campaigns');
Route::get('offers', [DiscoveryController::class, 'offers'])->name('offers.index');
Route::get('offers/{offer:slug}', [DiscoveryController::class, 'showOffer'])->name('offers.show');
Route::get('doctors/{doctor:slug}', [DoctorController::class, 'show'])->name('doctors.show');
Route::get('doctors/{doctor:slug}/slots', SlotController::class)->name('doctors.slots');
Route::get('clinics/{clinic:slug}', [ClinicController::class, 'show'])->name('clinics.show');
Route::get('reference/geography', [DiscoveryController::class, 'geography'])->name('reference.geography');
Route::get('reference/specialties', [DiscoveryController::class, 'specialties'])->name('reference.specialties');
Route::get('reference/service-types', [DiscoveryController::class, 'serviceTypes'])->name('reference.service-types');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('customers', [CustomerController::class, 'register'])->name('customers.register');
    Route::post('customers/session', [CustomerController::class, 'login'])->name('customers.login');
    Route::post('customers/password/forgot', [CustomerController::class, 'forgotPassword'])->name('customers.password.forgot');
    Route::post('customers/password/reset', [CustomerController::class, 'resetPassword'])->name('customers.password.reset');
    Route::post('support/tickets', [SupportTicketController::class, 'store'])->name('support.tickets.store');
});

Route::get('customers/me', [CustomerController::class, 'show'])->name('customers.me');
Route::post('customers/me', [CustomerController::class, 'show'])->name('customers.me.lookup');
Route::put('customers/me', [CustomerController::class, 'update'])->name('customers.update');
Route::get('customers/bookings', [CustomerController::class, 'bookings'])->name('customers.bookings');
Route::post('customers/bookings', [CustomerController::class, 'bookings'])->name('customers.bookings.lookup');
