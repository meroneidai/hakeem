<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\BookingStatus;
use App\Enums\ClinicModule;
use App\Enums\LabOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LabOrder;
use App\Support\StatusTally;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesClinicContext;

    public function __invoke(Request $request): View
    {
        $clinic = $this->clinic($request)->load([
            'plan.featureFlags',
            'currentSubscription.plan',
            'addresses.city.governorate',
            'doctors.specialty',
            'services.serviceType',
        ]);

        $today = now()->toDateString();
        $todayBookings = $this->applyStaffBookingScope(
            Booking::query()->where('clinic_id', $clinic->id)->onDate($today),
            $request,
        );

        $todayLabs = LabOrder::query()->where('clinic_id', $clinic->id)->onDate($today);
        $access = $this->access($request);
        $showLabs = $clinic->hasModule(ClinicModule::Labs) && (! $access->isDoctor() || $access->isOwner());

        $upcoming = (clone $todayBookings)
            ->whereIn('status', [
                BookingStatus::Pending,
                BookingStatus::Confirmed,
                BookingStatus::InProgress,
            ])
            ->with(['patient', 'doctor', 'address', 'serviceType'])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return view('clinic.dashboard', [
            'clinic' => $clinic,
            'access' => $access,
            'pending' => $clinic->pendingSetupSteps(),
            'todayStatuses' => StatusTally::of($todayBookings, BookingStatus::cases()),
            'todayLabStatuses' => $showLabs ? StatusTally::of($todayLabs, LabOrderStatus::cases()) : [],
            'unpaidToday' => (clone $todayBookings)
                ->where('payment_status', 'unpaid')
                ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::NoShow])
                ->count(),
            'upcoming' => $upcoming,
            'showLabs' => $showLabs,
            'greetingDate' => now('Africa/Cairo'),
        ]);
    }
}
