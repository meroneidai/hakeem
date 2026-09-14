<?php

namespace App\Http\Controllers;

use App\Models\Governorate;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use Illuminate\View\View;

/**
 * Public landing page. The full discovery experience (Most Booked, Top Rated,
 * ratings) lands in Phase 7 once bookings exist; for now it renders the live
 * reference data and any running promotions so admin work is visible immediately.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'governorates' => Governorate::active()->ordered()->with('cities')->get(),
            'specialties' => Specialty::active()->ordered()->get(),
            'featuredSpecialties' => Specialty::active()->where('is_featured', true)->ordered()->get(),
            'serviceTypes' => ServiceType::active()->ordered()->get(),
            'promotions' => Promotion::running()->featured()->latest('starts_at')->limit(6)->get(),
        ]);
    }
}
