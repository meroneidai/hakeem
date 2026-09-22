<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\LabPackage;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Services\LoyaltyProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public marketplace landing page.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, LoyaltyProgram $loyalty): View
    {
        return view('home', [
            'seoDescription' => __('discover.hero_subtitle'),
            'heroTitle' => __('discover.hero_title'),
            'heroSubtitle' => __('discover.hero_subtitle'),
            'homeBody' => '',
            'governorates' => Governorate::active()->ordered()->with('cities')->get(),
            'specialties' => Specialty::active()->ordered()->withCount(['doctors' => fn ($query) => $query->active()])->get(),
            'serviceTypes' => ServiceType::active()->ordered()->get(),
            'promotions' => Promotion::running()->featured()->latest('starts_at')->limit(6)->get(),
            'packages' => LabPackage::query()->active()->featured()->with('tests')->ordered()->limit(3)->get(),
            'articles' => collect(),
            'doctors' => Doctor::query()->listable()->with(['specialty', 'clinics.addresses.city'])->orderByDesc('years_of_experience')->limit(6)->get(),
            'clinics' => Clinic::query()->listable()->with(['primaryAddress.city', 'doctors.specialty'])->orderBy('name_ar')->limit(6)->get(),
            'search' => $request->only(['q', 'governorate', 'city', 'specialty']),
            'signupCampaign' => $loyalty->activeSignupCampaign(),
        ]);
    }
}
