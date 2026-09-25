<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AgentSpeechController;
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
use App\Http\Controllers\DeeplinkController;
use App\Http\Controllers\DoctorDirectoryController;
use App\Http\Controllers\DocumentVerifyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LabCartController;
use App\Http\Controllers\LabCatalogController;
use App\Http\Controllers\LabCheckoutController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MedicalLibraryController;
use App\Http\Controllers\OfferDirectoryController;
use App\Http\Controllers\PatientRecordController;
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
Route::get('.well-known/apple-app-site-association', [DeeplinkController::class, 'appleAppSiteAssociation'])
    ->name('deeplinks.apple');
Route::get('.well-known/assetlinks.json', [DeeplinkController::class, 'assetLinks'])
    ->name('deeplinks.android');
Route::get('.well-known/hakeem-app-links.json', [DeeplinkController::class, 'appLinks'])
    ->name('deeplinks.manifest');
Route::post('agent/messages', [AgentController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('agent.messages');
Route::post('agent/speech', AgentSpeechController::class)
    ->middleware('throttle:20,1')
    ->name('agent.speech');

Route::get('doctors', [DoctorDirectoryController::class, 'index'])->name('doctors.index');
Route::get('doctors/{doctor:slug}/slots', SlotController::class)->name('doctors.slots');
Route::get('doctors/{doctor:slug}', [DoctorDirectoryController::class, 'show'])->name('doctors.show');
Route::get('clinics', [ClinicDirectoryController::class, 'index'])->name('clinics.index');
Route::get('clinics/{clinic:slug}', [ClinicDirectoryController::class, 'show'])->name('clinics.show');
Route::get('specialties', [SpecialtyDirectoryController::class, 'index'])->name('specialties.index');
Route::get('specialties/{specialty:slug}/{governorate:slug}/{city:slug}', [SpecialtyDirectoryController::class, 'showCity'])->name('specialties.city');
Route::get('specialties/{specialty:slug}/{location}', [SpecialtyDirectoryController::class, 'showLocation'])->name('specialties.location');
Route::get('specialties/{specialty:slug}', [SpecialtyDirectoryController::class, 'show'])->name('specialties.show');
Route::get('services', [ServiceDirectoryController::class, 'index'])->name('services.index');
Route::get('home-care', [ServiceDirectoryController::class, 'homeCare'])->name('home-care');
Route::get('teleconsultation', [ServiceDirectoryController::class, 'teleconsultation'])->name('teleconsultation');
Route::get('services/{serviceType:slug}/{governorate:slug}/{city:slug}', [ServiceDirectoryController::class, 'showCity'])->name('services.city');
Route::get('services/{serviceType:slug}/{location}', [ServiceDirectoryController::class, 'showLocation'])->name('services.location');
Route::get('services/{serviceType:slug}', [ServiceDirectoryController::class, 'show'])->name('services.show');
Route::get('cities', [CityDirectoryController::class, 'index'])->name('cities.index');
Route::get('cities/{governorate:slug}/{city:slug}', [CityDirectoryController::class, 'showNested'])->name('cities.nested');
Route::get('cities/{city:slug}', [CityDirectoryController::class, 'show'])->name('cities.show');
Route::get('medical-library', [MedicalLibraryController::class, 'index'])->name('library.index');
Route::get('medical-library/{article:slug}', [MedicalLibraryController::class, 'show'])->name('library.show');

Route::get('how-it-works', [SitePageController::class, 'howItWorks'])->name('how-it-works');
Route::get('about', [SitePageController::class, 'about'])->name('about');
Route::get('help', [SitePageController::class, 'help'])->name('help');
Route::get('help/{topic}', [SitePageController::class, 'helpTopic'])->name('help.topic');
Route::get('terms', [SitePageController::class, 'terms'])->name('terms');
Route::get('privacy', [SitePageController::class, 'privacy'])->name('privacy');
Route::get('cookies', [SitePageController::class, 'cookies'])->name('cookies');
Route::get('cancellation-policy', [SitePageController::class, 'cancellation'])->name('cancellation');
Route::get('medical-disclaimer', [SitePageController::class, 'medicalDisclaimer'])->name('disclaimer');
Route::get('accessibility', [SitePageController::class, 'accessibility'])->name('accessibility');
Route::get('contact', [ContactController::class, 'create'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('complaints', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');

Route::middleware('auth')->group(function () {
    Route::get('book/doctors/{doctor:slug}', [BookingController::class, 'create'])->name('book.doctors.create');
    Route::post('book/doctors/{doctor:slug}', [BookingController::class, 'store'])->name('book.doctors.store');
    Route::post('book/offers/{offer:slug}', [BookingController::class, 'storeOffer'])->name('book.offers.store');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{booking}/video', [AppointmentController::class, 'video'])->name('appointments.video');
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
    Route::get('account/records', [PatientRecordController::class, 'index'])->name('records.index');
    Route::get('account/records/{careDocument}', [PatientRecordController::class, 'show'])->name('records.show');
    Route::get('account/records/{careDocument}/pdf', [PatientRecordController::class, 'pdf'])->name('records.pdf');
});
Route::get('verify/{code}', DocumentVerifyController::class)->name('documents.verify');
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
| Authentication (phone or email — first character decides the field)
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
        Route::resource('insurance-providers', Admin\InsuranceProviderController::class)->except('show');
        Route::resource('service-types', Admin\ServiceTypeController::class)
            ->except(['show'])
            ->parameters(['service-types' => 'serviceType']);

        // Subscriptions, discounts and payments
        Route::resource('plans', Admin\SubscriptionPlanController::class)
            ->except('show')
            ->parameters(['plans' => 'plan']);
        Route::resource('discount-codes', Admin\DiscountCodeController::class)->except('show');
        Route::get('payments', [Admin\PaymentSettingsController::class, 'edit'])->name('payments.edit');
        Route::put('payments', [Admin\PaymentSettingsController::class, 'update'])->name('payments.update');

        // Growth
        Route::resource('lab-tests', Admin\LabTestController::class)
            ->except('show')
            ->parameters(['lab-tests' => 'labTest']);
        Route::resource('lab-packages', Admin\LabPackageController::class)
            ->except('show')
            ->parameters(['lab-packages' => 'labPackage']);
        Route::resource('promotions', Admin\PromotionController::class)->except('show');
        Route::post('promotions/{promotion}/approve', [Admin\PromotionController::class, 'approve'])->name('promotions.approve');
        Route::post('promotions/{promotion}/reject', [Admin\PromotionController::class, 'reject'])->name('promotions.reject');
        Route::resource('articles', Admin\MedicalArticleController::class)
            ->except('show')
            ->parameters(['articles' => 'article']);
        Route::resource('site-pages', Admin\SitePageController::class)
            ->except('show')
            ->parameters(['site-pages' => 'sitePage']);
        Route::post('seo-pages/generate', [Admin\SeoPageController::class, 'generate'])->name('seo-pages.generate');
        Route::get('seo/site', [Admin\SiteSeoController::class, 'edit'])->name('seo.site.edit');
        Route::put('seo/site', [Admin\SiteSeoController::class, 'update'])->name('seo.site.update');
        Route::resource('seo-pages', Admin\SeoPageController::class)
            ->only(['index', 'edit', 'update', 'destroy'])
            ->parameters(['seo-pages' => 'seoPage']);

        // Operations
        Route::get('bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/create', [Admin\BookingController::class, 'create'])->name('bookings.create');
        Route::get('bookings/patients/lookup', [Admin\BookingController::class, 'lookupPatient'])->name('bookings.patients.lookup');
        Route::post('bookings', [Admin\BookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{booking}', [Admin\BookingController::class, 'update'])->name('bookings.update');
        Route::get('lab-orders', [Admin\LabOrderController::class, 'index'])->name('lab-orders.index');
        Route::get('lab-orders/{labOrder}', [Admin\LabOrderController::class, 'show'])->name('lab-orders.show');
        Route::put('lab-orders/{labOrder}', [Admin\LabOrderController::class, 'update'])->name('lab-orders.update');

        Route::get('support', [Admin\SupportTicketController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [Admin\SupportTicketController::class, 'show'])->name('support.show');
        Route::put('support/{ticket}', [Admin\SupportTicketController::class, 'update'])->name('support.update');
        Route::post('support/{ticket}/reply', [Admin\SupportTicketController::class, 'reply'])->name('support.reply');
        Route::get('agent-conversations', [Admin\AgentConversationController::class, 'index'])->name('agent-conversations.index');
        Route::get('agent-conversations/{agentConversation}', [Admin\AgentConversationController::class, 'show'])->name('agent-conversations.show');

        Route::resource('staff', Admin\StaffController::class)->except('show');
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
        Route::get('clinics', [Admin\ClinicController::class, 'index'])->name('clinics.index');
        Route::get('clinics/{clinic}', [Admin\ClinicController::class, 'show'])->name('clinics.show');
        Route::put('clinics/{clinic}', [Admin\ClinicController::class, 'update'])->name('clinics.update');
        Route::delete('clinics/{clinic}', [Admin\ClinicController::class, 'destroy'])->name('clinics.destroy');
        Route::get('doctors', [Admin\DoctorController::class, 'index'])->name('doctors.index');
        Route::put('doctors/{doctor}', [Admin\DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('doctors/{doctor}', [Admin\DoctorController::class, 'destroy'])->name('doctors.destroy');
        Route::get('billing', [Admin\BillingController::class, 'index'])->name('billing');
        Route::get('billing/{subscription}', [Admin\BillingController::class, 'show'])->name('billing.show');
        Route::put('billing/{subscription}', [Admin\BillingController::class, 'update'])->name('billing.update');
        Route::post('billing/{subscription}/invoice', [Admin\BillingController::class, 'sendInvoice'])->name('billing.invoice');
        Route::post('billing/{subscription}/reminder', [Admin\BillingController::class, 'sendReminder'])->name('billing.reminder');
        Route::get('map', Admin\MapController::class)->name('map');
        Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/clock-in', [Admin\AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('attendance/clock-out', [Admin\AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
        Route::get('errors', [Admin\ExceptionReportController::class, 'index'])->name('errors.index');
        Route::get('errors/{error}', [Admin\ExceptionReportController::class, 'show'])->name('errors.show');
        Route::put('errors/{error}', [Admin\ExceptionReportController::class, 'update'])->name('errors.update');

        Route::get('notification-settings', [Admin\NotificationSettingsController::class, 'edit'])->name('notifications.edit');
        Route::put('notification-settings', [Admin\NotificationSettingsController::class, 'update'])->name('notifications.update');

        Route::get('system', [Admin\SystemSettingsController::class, 'edit'])->name('system.edit');
        Route::put('system', [Admin\SystemSettingsController::class, 'update'])->name('system.update');
        Route::post('system/probe', [Admin\SystemSettingsController::class, 'probe'])->name('system.probe');

        Route::get('loyalty', [Admin\LoyaltySettingsController::class, 'edit'])->name('loyalty.edit');
        Route::put('loyalty', [Admin\LoyaltySettingsController::class, 'update'])->name('loyalty.update');

        Route::get('analytics', Admin\AnalyticsController::class)->name('analytics');
        Route::put('analytics', [Admin\AnalyticsController::class, 'update'])->name('analytics.update');
        Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::put('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');

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

        Route::get('labs', [Clinic\LabOfferingController::class, 'edit'])->name('labs.edit')->middleware('clinic-module:labs');
        Route::put('labs', [Clinic\LabOfferingController::class, 'update'])->name('labs.update')->middleware('clinic-module:labs');
        Route::get('lab-orders', [Clinic\LabOrderController::class, 'index'])->name('lab-orders.index')->middleware('clinic-module:labs');
        Route::get('lab-orders/{labOrder}', [Clinic\LabOrderController::class, 'show'])->name('lab-orders.show')->middleware('clinic-module:labs');
        Route::put('lab-orders/{labOrder}', [Clinic\LabOrderController::class, 'update'])->name('lab-orders.update')->middleware('clinic-module:labs');

        Route::get('care-documents/create', [Clinic\CareDocumentController::class, 'create'])->name('care.create');
        Route::post('care-documents', [Clinic\CareDocumentController::class, 'store'])->name('care.store');
        Route::get('care-documents/{careDocument}', [Clinic\CareDocumentController::class, 'show'])->name('care.show');

        Route::resource('offers', Clinic\OfferController::class)->except('show')->middleware('clinic-module:promotions');

        Route::get('subscription', [Clinic\SubscriptionController::class, 'edit'])->name('subscription.edit');
        Route::put('subscription', [Clinic\SubscriptionController::class, 'update'])->name('subscription.update');

        Route::get('payments', [Clinic\PaymentSettingsController::class, 'edit'])->name('payments.edit');
        Route::put('payments', [Clinic\PaymentSettingsController::class, 'update'])->name('payments.update');

        Route::get('staff', [Clinic\StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/create', [Clinic\StaffController::class, 'create'])->name('staff.create');
        Route::post('staff', [Clinic\StaffController::class, 'store'])->name('staff.store');
        Route::delete('staff/{user}', [Clinic\StaffController::class, 'destroy'])->name('staff.destroy');

        Route::get('queue', [Clinic\BookingController::class, 'index'])->name('queue.index');
        Route::get('queue/create', [Clinic\BookingController::class, 'create'])->name('queue.create');
        Route::post('queue', [Clinic\BookingController::class, 'store'])->name('queue.store');
        Route::put('queue/{booking}', [Clinic\BookingController::class, 'update'])->name('queue.update');
        Route::get('bookings', [Clinic\BookingController::class, 'history'])->name('bookings.index');

        Route::get('attendance', [Clinic\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/clock-in', [Clinic\AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
        Route::post('attendance/clock-out', [Clinic\AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    });

Route::get('{sitePage:slug}', [SitePageController::class, 'show'])
    ->where('sitePage', '^[a-z0-9]+(?:-[a-z0-9]+)*$')
    ->name('pages.show');
