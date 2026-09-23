<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\LabOrderStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ExceptionReport;
use App\Models\Governorate;
use App\Models\LabOrder;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\MarketplaceInsights;
use App\Support\Settings;
use App\Support\StatusTally;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Settings $settings, MarketplaceInsights $insights): View
    {
        $today = now()->toDateString();
        $todayBookings = Booking::query()->onDate($today);
        $todayLabs = LabOrder::query()->onDate($today);

        $counts = [
            'governorates' => Governorate::count(),
            'cities' => City::count(),
            'specialties' => Specialty::count(),
            'service_types' => ServiceType::count(),
            'plans' => SubscriptionPlan::count(),
            'promotions_running' => Promotion::running()->count(),
            'open_tickets' => SupportTicket::unresolved()->count(),
            'staff' => User::whereHas('roles', fn ($q) => $q->whereIn('name', array_map(
                fn (RoleName $role) => $role->value,
                RoleName::internalStaff(),
            )))->count(),
            'patients' => User::whereHas('roles', fn ($q) => $q->where('name', RoleName::Patient->value))->count(),
            'clinics' => Clinic::count(),
            'bookings' => Booking::count(),
            'bookings_today' => (clone $todayBookings)->count(),
            'labs_today' => (clone $todayLabs)->count(),
            'unpaid_today' => (clone $todayBookings)
                ->where('payment_status', 'unpaid')
                ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::NoShow])
                ->count(),
            'open_errors' => ExceptionReport::query()->whereNull('resolved_at')->count(),
        ];

        $checklist = [
            'geography' => $counts['governorates'] > 0 && $counts['cities'] > 0,
            'specialties' => $counts['specialties'] > 0,
            'service_types' => $counts['service_types'] >= 6,
            'plans' => $counts['plans'] >= 3,
            'payments' => filled($settings->get('payments.allowed_modes')),
            'notifications' => filled($settings->get('notifications.channels')),
        ];

        $recentActivity = AuditLog::with('user')->latest('created_at')->limit(8)->get();
        $insightsSnapshot = $insights->snapshot();

        $recentBookings = Booking::query()
            ->with(['patient', 'clinic', 'doctor'])
            ->latest('scheduled_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'counts' => $counts,
            'checklist' => $checklist,
            'recentActivity' => $recentActivity,
            'insightsSnapshot' => $insightsSnapshot,
            'todayStatuses' => StatusTally::of($todayBookings, BookingStatus::cases()),
            'todayLabStatuses' => StatusTally::of($todayLabs, LabOrderStatus::cases()),
            'recentBookings' => $recentBookings,
            'greetingDate' => now('Africa/Cairo'),
        ]);
    }
}
