<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\SlotController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\ClinicRegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CityDirectoryController;
use App\Http\Controllers\Clinic;
use App\Http\Controllers\ClinicDirectoryController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DoctorDirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LabCartController;
use App\Http\Controllers\LabCatalogController;
use App\Http\Controllers\LabCheckoutController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OfferDirectoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceDirectoryController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SitePageController;
use App\Http\Controllers\SpecialtyDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('locale', LocaleController::class)->name('locale.switch');

Route::get('search', SearchController::class)->name('search');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::post('agent/messages', [AgentController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('agent.messages');

Route::get('doctors', [DoctorDirectoryController::class, 'index'])->name('doctors.index');
Route::get('doctors/{doctor:slug}/slots', SlotController::class)->name('doctors.slots');
Route::get('doctors/{doctor:slug}', [DoctorDirectoryController::class, 'show'])->name('doctors.show');
Route::get('clinics', [ClinicDirectoryController::class, 'index'])->name('clinics.index');
Route::get('clinics/{clinic:slug}', [ClinicDirectoryController::class, 'show'])->name('clinics.show');
Route::get('specialties', [SpecialtyDirectoryController::class, 'index'])->name('specialties.index');
Route::get('specialties/{specialty:slug}', [SpecialtyDirectoryController::class, 'show'])->name('specialties.show');
Route::get('services', [ServiceDirectoryController::class, 'index'])->name('services.index');
Route::get('home-care', [ServiceDirectoryController::class, 'homeCare'])->name('home-care');
Route::get('teleconsultation', [ServiceDirectoryController::class, 'teleconsultation'])->name('teleconsultation');
Route::get('services/{serviceType:slug}', [ServiceDirectoryController::class, 'show'])->name('services.show');
Route::get('cities', [CityDirectoryController::class, 'index'])->name('cities.index');
Route::get('cities/{city:slug}', [CityDirectoryController::class, 'show'])->name('cities.show');

Route::get('how-it-works', [SitePageController::class, 'howItWorks'])->name('how-it-works');
Route::get('about', [SitePageController::class, 'about'])->name('about');
Route::get('help', [SitePageController::class, 'help'])->name('help');
Route::get('terms', [SitePageController::class, 'terms'])->name('terms');
Route::get('privacy', [SitePageController::class, 'privacy'])->name('privacy');
Route::get('cookies', [SitePageController::class, 'cookies'])->name('cookies');
Route::get('cancellation-policy', [SitePageController::class, 'cancellation'])->name('cancellation');
Route::get('medical-disclaimer', [SitePageController::class, 'medicalDisclaimer'])->name('disclaimer');
Route::get('contact', [ContactController::class, 'create'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('complaints', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');

Route::middleware('auth')->group(function () {
    Route::get('book/doctors/{doctor:slug}', [BookingController::class, 'create'])->name('book.doctors.create');
    Route::post('book/doctors/{doctor:slug}', [BookingController::class, 'store'])->name('book.doctors.store');
    Route::post('book/offers/{offer:slug}', [BookingController::class, 'storeOffer'])->name('book.offers.store');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::delete('appointments/{booking}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::delete('lab-orders/{labOrder}', [AppointmentController::class, 'destroyLabOrder'])->name('appointments.lab-orders.destroy');
    Route::post('appointments/{booking}/review', [ReviewController::class, 'store'])->name('appointments.review');
    Route::get('account', [ProfileController::class, 'edit'])->name('account.edit');
    Route::put('account', [ProfileController::class, 'update'])->name('account.update');
    Route::post('account/phone/code', [ProfileController::class, 'sendPhoneCode'])->name('account.phone.code');
    Route::post('account/phone/verify', [ProfileController::class, 'verifyPhone'])->name('account.phone.verify');
    Route::post('account/email/resend', [ProfileController::class, 'sendEmailLink'])->name('account.email.resend');
    Route::get('account/verify-email/{id}/{hash}', [ProfileController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::put('inbox/{notification}', [InboxController::class, 'update'])->name('inbox.update');
});

Route::get('labs', [LabCatalogController::class, 'index'])->name('labs.index');
Route::get('labs/tests/{labTest:slug}', [LabCatalogController::class, 'showTest'])->name('labs.tests.show');
Route::get('labs/packages/{labPackage:slug}', [LabCatalogController::class, 'showPackage'])->name('labs.packages.show');
Route::get('labs/cart', [LabCartController::class, 'show'])->name('labs.cart');
Route::post('labs/cart', [LabCartController::class, 'store'])->name('labs.cart.store');
Route::patch('labs/cart', [LabCartController::class, 'update'])->name('labs.cart.update');
Route::delete('labs/cart', [LabCartController::class, 'destroy'])->name('labs.cart.destroy');
Route::middleware('auth')->group(function () {
    Route::get('labs/checkout', [LabCheckoutController::class, 'create'])->name('labs.checkout');
    Route::post('labs/checkout', [LabCheckoutController::class, 'store'])->name('labs.checkout.store');
    Route::get('labs/orders/{labOrder}', [LabCheckoutController::class, 'show'])->name('labs.orders.show');
});
Route::get('offers', [OfferDirectoryController::class, 'index'])->name('offers.index');
Route::get('offers/{offer:slug}', [OfferDirectoryController::class, 'show'])->name('offers.show');

/*
|--------------------------------------------------------------------------
| Authentication (phone-number based)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('admin/login', [AdminLoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
    Route::get('register/clinic', [ClinicRegisterController::class, 'create'])->name('register.clinic');
    Route::post('register/clinic', [ClinicRegisterController::class, 'store']);
    Route::get('password/forgot', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('password/forgot', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('password/reset', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'store'])->name('password.update');
    Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
    Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');
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
