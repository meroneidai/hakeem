<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Settings;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Settings $settings): View
    {
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
        ];

        // Exit criteria for Phase 1: reference data configured before clinics can register.
        $checklist = [
            'geography' => $counts['governorates'] > 0 && $counts['cities'] > 0,
            'specialties' => $counts['specialties'] > 0,
            'service_types' => $counts['service_types'] >= 6,
            'plans' => $counts['plans'] >= 3,
            'payments' => filled($settings->get('payments.allowed_modes')),
            'notifications' => filled($settings->get('notifications.channels')),
        ];

        $recentActivity = AuditLog::with('user')->latest('created_at')->limit(8)->get();

        return view('admin.dashboard', compact('counts', 'checklist', 'recentActivity'));
    }
}
