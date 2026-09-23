<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Services\BookingManager;
use App\Services\PaymentOptions;
use App\Support\BookingDays;
use App\Support\StatusTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    use ResolvesClinicContext;

    public function __construct(
        private BookingManager $bookings,
        private PaymentOptions $payments,
    ) {}

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        $request->merge([
            'status' => $request->filled('status') ? $request->input('status') : null,
            'address' => $request->filled('address') ? $request->input('address') : null,
        ]);

        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'address' => ['nullable', 'integer'],
        ]);

        $date = $filters['date'] ?? now()->toDateString();

        $dayQuery = $this->applyStaffBookingScope(
            Booking::query()->where('clinic_id', $clinic->id)->onDate($date),
            $request,
        );

        $bookings = (clone $dayQuery)
            ->with(['patient', 'doctor', 'address', 'serviceType', 'clinicService'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['address'] ?? null, fn ($query, $addressId) => $query->where('clinic_address_id', $addressId))
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        $statusCounts = StatusTally::of($dayQuery, BookingStatus::cases());

        return view('clinic.queue.index', [
            'clinic' => $clinic,
            'bookings' => $bookings,
            'addresses' => $clinic->addresses()->active()->ordered()->get(),
            'filters' => $filters + ['date' => $date],
            'pendingCount' => $statusCounts[BookingStatus::Pending->value] ?? 0,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function history(Request $request): View
    {
        $clinic = $this->clinic($request);

        $request->merge([
            'status' => $request->filled('status') ? $request->input('status') : null,
            'doctor' => $request->filled('doctor') ? $request->input('doctor') : null,
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = $this->applyStaffBookingScope(
            Booking::query()->where('clinic_id', $clinic->id),
            $request,
        )
            ->with(['patient', 'doctor', 'address', 'serviceType', 'clinicService'])
            ->when($filters['q'] ?? null, function ($builder, $term) {
                $builder->where(function ($inner) use ($term) {
                    $inner->whereHas('patient', fn ($patient) => $patient
                        ->whereLike('name', "%{$term}%")
                        ->orWhereLike('phone', "%{$term}%"));
                });
            })
            ->when($filters['doctor'] ?? null, fn ($builder, $doctorId) => $builder->where('doctor_id', $doctorId))
            ->when($filters['from'] ?? null, fn ($builder, $from) => $builder->whereDate('scheduled_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($builder, $to) => $builder->whereDate('scheduled_at', '<=', $to));

        $bookings = (clone $query)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->latest('scheduled_at')
            ->paginate(25)
            ->withQueryString();

        return view('clinic.bookings.index', [
            'clinic' => $clinic,
            'bookings' => $bookings,
            'statusCounts' => StatusTally::of($query, BookingStatus::cases()),
            'doctors' => $clinic->doctors()->orderBy('name_ar')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $clinic = $this->clinic($request)->load([
            'doctors' => fn ($query) => $query->where('doctors.is_active', true)->orderBy('name_ar'),
            'addresses' => fn ($query) => $query->active()->ordered(),
            'services' => fn ($query) => $query->active()->with('serviceType'),
        ]);

        $serviceTypes = $clinic->services
            ->pluck('serviceType')
            ->filter()
            ->unique('id')
            ->values();

        if ($serviceTypes->isEmpty()) {
            $serviceTypes = ServiceType::query()->active()->ordered()->get();
        }

        return view('clinic.queue.create', [
            'clinic' => $clinic,
            'serviceTypes' => $serviceTypes,
            'paymentModes' => collect($this->payments->platformModes())
                ->merge($serviceTypes->flatMap(fn (ServiceType $type) => $this->payments->allowedModes($clinic, $type)))
                ->unique(fn (PaymentMode $mode) => $mode->value)
                ->values()
                ->all(),
            'defaultPaymentMode' => $this->payments->defaultMode($clinic, $serviceTypes->first()),
            'gatewayReady' => $this->payments->isGatewayConfigured(),
            'dayOptions' => BookingDays::upcoming(),
            'serviceFlags' => $serviceTypes->mapWithKeys(function (ServiceType $type) use ($clinic) {
                $offering = $clinic->services->firstWhere('service_type_id', $type->id);

                return [(string) $type->id => [
                    'duration_minutes' => $type->durationMinutes($offering?->duration_minutes),
                    'payment_modes' => $type->allowed_payment_modes ?? [],
                ]];
            })->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = $this->clinic($request);

        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:160'],
            'patient_phone' => ['required', 'string', 'max:32'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'clinic_address_id' => ['required', 'exists:clinic_addresses,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'scheduled_at' => ['required', 'date'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
        ]);

        $doctor = Doctor::query()->findOrFail($validated['doctor_id']);
        $address = ClinicAddress::query()->findOrFail($validated['clinic_address_id']);

        abort_unless($this->bookings->doctorBelongsToClinic($doctor, $clinic), 404);
        abort_unless($this->bookings->addressBelongsToClinic($address, $clinic), 404);

        $rawMode = $validated['payment_mode'] ?? null;
        $serviceType = ServiceType::query()->findOrFail($validated['service_type_id']);
        $mode = $rawMode instanceof PaymentMode
            ? $rawMode
            : ($rawMode ? PaymentMode::from($rawMode) : $this->payments->defaultMode($clinic, $serviceType));

        if (! $this->payments->allows($clinic, $mode, $serviceType)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        $validated['payment_mode'] = $mode;

        $this->bookings->createForClinic($clinic, $validated, $request->user());

        return redirect()
            ->route('clinic.queue.index', ['date' => Carbon::parse($validated['scheduled_at'])->toDateString()])
            ->with('status', __('clinic.queue.created'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->assertOwned($request, $booking);

        $validated = $request->validate([
            'action' => ['required', Rule::in([
                'confirm', 'check_in', 'complete', 'cancel', 'no_show', 'reschedule', 'mark_paid', 'mark_unpaid', 'evaluation_note',
            ])],
            'scheduled_at' => ['required_if:action,reschedule', 'nullable', 'date', 'after:now'],
            'evaluation_notes' => ['required_if:action,evaluation_note', 'nullable', 'string', 'max:4000'],
        ]);

        $actor = $request->user();

        match ($validated['action']) {
            'confirm' => $this->bookings->transition($booking, BookingStatus::Confirmed, $actor),
            'check_in' => $this->bookings->transition($booking, BookingStatus::InProgress, $actor),
            'complete' => $this->bookings->transition($booking, BookingStatus::Completed, $actor),
            'cancel' => $this->bookings->transition($booking, BookingStatus::Cancelled, $actor),
            'no_show' => $this->bookings->transition($booking, BookingStatus::NoShow, $actor),
            'reschedule' => $this->bookings->reschedule($booking, $validated['scheduled_at'], $actor),
            'mark_paid' => $this->bookings->markPayment($booking, 'paid'),
            'mark_unpaid' => $this->bookings->markPayment($booking, 'unpaid'),
            'evaluation_note' => $this->bookings->recordEvaluationNotes($booking, (string) $validated['evaluation_notes']),
        };

        return back()->with('status', __('clinic.queue.updated'));
    }
}
