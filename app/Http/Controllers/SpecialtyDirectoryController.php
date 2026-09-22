<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use Illuminate\View\View;

class SpecialtyDirectoryController extends Controller
{
    public function index(): View
    {
        $specialties = Specialty::query()
            ->active()
            ->ordered()
            ->withCount(['doctors' => fn ($query) => $query->active()])
            ->get();

        return view('specialties.index', compact('specialties'));
    }

    public function show(Specialty $specialty): View
    {
        abort_unless($specialty->is_active, 404);

        $specialty->load(['doctors' => fn ($query) => $query->active()->with(['specialty', 'clinics.addresses.city'])]);

        return view('specialties.show', compact('specialty'));
    }
}
