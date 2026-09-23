<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Clinic;
use App\Services\BookingManager;
use App\Support\Audit;
use App\Support\StatusTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller implements HasMiddleware
{
    public function __construct(private BookingManager $bookings) {}

    public static function middleware(): array
    {
        return ['can:'.Permission::OverseeBookings->value];
    }

    public function index(Request $request): View
    {
        $request->merge([
            'status' => $request->filled('status') ? $request->input('status') : null,
            'clinic' => $request->filled('clinic') ? $request->input('clinic') : null,
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'clinic' => ['nullable', 'integer', 'exists:clinics,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Booking::query()
            ->with(['patient', 'clinic', 'doctor', 'serviceType', 'clinicService'])
            ->when($filters['q'] ?? null, function ($builder, $term) {
                $builder->where(function ($inner) use ($term) {
                    if (ctype_digit($term)) {
                        $inner->orWhere('id', (int) $term);
                    }

                    $inner->orWhereHas('patient', fn ($patient) => $patient
                        ->whereLike('name', "%{$term}%")
                        ->orWhereLike('phone', "%{$term}%"))
                        ->orWhereHas('clinic', fn ($clinic) => $clinic
                            ->whereLike('name_ar', "%{$term}%")
                            ->orWhereLike('name_en', "%{$term}%"));
                });
            })
            ->when($filters['clinic'] ?? null, fn ($builder, $clinicId) => $builder->where('clinic_id', $clinicId))
            ->when($filters['from'] ?? null, fn ($builder, $from) => $builder->whereDate('scheduled_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($builder, $to) => $builder->whereDate('scheduled_at', '<=', $to));

        $bookings = (clone $query)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->latest('scheduled_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'statusCounts' => StatusTally::of($query, BookingStatus::cases()),
            'clinics' => Clinic::query()->orderBy('name_ar')->limit(200)->get(['id', 'name_ar', 'name_en']),
            'filters' => $filters,
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'patient',
            'clinic',
            'doctor.specialty',
            'address.city',
            'serviceType',
            'clinicService',
            'statusHistory.changedBy',
            'review',
        ]);

        return view('admin.bookings.show', [
            'booking' => $booking,
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['cancel'])],
        ]);

        if ($validated['action'] === 'cancel') {
            $this->bookings->transition($booking, BookingStatus::Cancelled, $request->user());
            Audit::log('booking.cancelled', $booking, ['status' => BookingStatus::Cancelled->value]);
        }

        return back()->with('status', __('admin.bookings.cancelled'));
    }
}
