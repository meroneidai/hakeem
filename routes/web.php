<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ClinicRegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Clinic;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('locale', LocaleController::class)->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Authentication (phone-number based)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
    Route::get('register/clinic', [ClinicRegisterController::class, 'create'])->name('register.clinic');
    Route::post('register/clinic', [ClinicRegisterController::class, 'store']);
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Platform Admin Dashboard (Phase 1)
|--------------------------------------------------------------------------
| Every route is behind authentication plus a server-side permission check.
*/

Route::middleware(['auth', 'internal-staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // Reference data
        Route::resource('governorates', Admin\GovernorateController::class)->except('show');
        Route::resource('cities', Admin\CityController::class)->except('show');
        Route::resource('specialties', Admin\SpecialtyController::class)->except('show');
        Route::resource('service-types', Admin\ServiceTypeController::class)
            ->except(['show', 'destroy'])
            ->parameters(['service-types' => 'serviceType']);

        // Subscriptions, discounts and payments
        Route::resource('plans', Admin\SubscriptionPlanController::class)
            ->except('show')
            ->parameters(['plans' => 'plan']);
        Route::resource('discount-codes', Admin\DiscountCodeController::class)->except('show');
        Route::get('payments', [Admin\PaymentSettingsController::class, 'edit'])->name('payments.edit');
        Route::put('payments', [Admin\PaymentSettingsController::class, 'update'])->name('payments.update');

        // Growth
        Route::resource('promotions', Admin\PromotionController::class)->except('show');
        Route::post('seo-pages/generate', [Admin\SeoPageController::class, 'generate'])->name('seo-pages.generate');
        Route::resource('seo-pages', Admin\SeoPageController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->parameters(['seo-pages' => 'seoPage']);

        // Operations
        Route::get('support', [Admin\SupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [Admin\SupportTicketController::class, 'show'])->name('support.show');
        Route::put('support/{ticket}', [Admin\SupportTicketController::class, 'update'])->name('support.update');
        Route::post('support/{ticket}/reply', [Admin\SupportTicketController::class, 'reply'])->name('support.reply');

        Route::resource('staff', Admin\StaffController::class)->except('show');

        Route::get('notification-settings', [Admin\NotificationSettingsController::class, 'edit'])->name('notifications.edit');
        Route::put('notification-settings', [Admin\NotificationSettingsController::class, 'update'])->name('notifications.update');

        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Clinic Dashboard (Phase 2)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'clinic-staff'])
    ->prefix('clinic')
    ->name('clinic.')
    ->group(function () {
        Route::get('/', Clinic\DashboardController::class)->name('dashboard');

        Route::get('profile', [Clinic\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [Clinic\ProfileController::class, 'update'])->name('profile.update');

        Route::resource('addresses', Clinic\AddressController::class)->except('show');
        Route::resource('doctors', Clinic\DoctorController::class)->except('show');

        Route::get('services', [Clinic\ServiceController::class, 'edit'])->name('services.edit');
        Route::put('services', [Clinic\ServiceController::class, 'update'])->name('services.update');

        Route::get('subscription', [Clinic\SubscriptionController::class, 'edit'])->name('subscription.edit');
        Route::put('subscription', [Clinic\SubscriptionController::class, 'update'])->name('subscription.update');

        Route::get('staff', [Clinic\StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [Clinic\StaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [Clinic\StaffController::class, 'store'])->name('staff.store');
        Route::delete('staff/{user}', [Clinic\StaffController::class, 'destroy'])->name('staff.destroy');
    });
