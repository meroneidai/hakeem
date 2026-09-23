<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class DoctorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ModerateClinics->value];
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:140'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $doctors = Doctor::query()
            ->with(['specialty', 'clinics:id,name_ar,name_en,slug'])
            ->withRatings()
            ->withCount(['bookings as completed_bookings_count' => fn ($bookings) => $bookings->where('status', BookingStatus::Completed)])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($inner) => $inner
                ->whereLike('name_ar', "%{$term}%")
                ->orWhereLike('name_en', "%{$term}%")
                ->orWhereLike('credentials', "%{$term}%")))
            ->when($filters['specialty'] ?? null, fn ($query, $slug) => $query->whereHas(
                'specialty',
                fn ($specialty) => $specialty->where('slug', $slug)
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name_ar')
            ->paginate(25)
            ->withQueryString();

        return view('admin.doctors.index', [
            'doctors' => $doctors,
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'filters' => $filters,
        ]);
    }
}
