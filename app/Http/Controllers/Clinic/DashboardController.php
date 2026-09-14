<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
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

        return view('clinic.dashboard', [
            'clinic' => $clinic,
            'access' => $this->access($request),
            'pending' => $clinic->pendingSetupSteps(),
        ]);
    }
}
