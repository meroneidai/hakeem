<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\BookingManager;
use App\Services\PaymentOptions;
use App\Support\Audit;
use App\Support\BookingDays;
use App\Support\StatusTally;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller implements HasMiddleware
{
    public function __construct(
        private BookingManager $bookings,
        private PaymentOptions $payments,
    ) {}

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

    public function create(Request $request): View
    {
        $clinics = Clinic::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->limit(300)
            ->get(['id', 'name_ar', 'name_en']);

        $clinicId = $request->integer('clinic') ?: null;
        $clinic = $clinicId
            ? Clinic::query()
                ->with([
                    'doctors' => fn ($query) => $query->where('doctors.is_active', true)->orderBy('name_ar'),
                    'addresses' => fn ($query) => $query->active()->ordered(),
                    'services' => fn ($query) => $query->active()->with('serviceType'),
                ])
                ->findOrFail($clinicId)
            : null;

        $serviceTypes = collect();
        $paymentModes = $this->payments->platformModes();
        $defaultPaymentMode = $this->payments->defaultMode();
        $serviceFlags = [];

        if ($clinic) {
            $serviceTypes = $clinic->services
                ->pluck('serviceType')
                ->filter()
                ->unique('id')
                ->values();

            if ($serviceTypes->isEmpty()) {
                $serviceTypes = ServiceType::query()->active()->ordered()->get();
            }

            $paymentModes = collect($this->payments->platformModes())
                ->merge($serviceTypes->flatMap(fn (ServiceType $type) => $this->payments->allowedModes($clinic, $type)))
                ->unique(fn (PaymentMode $mode) => $mode->value)
                ->values()
                ->all();

            $defaultPaymentMode = $this->payments->defaultMode($clinic, $serviceTypes->first());
            $serviceFlags = $serviceTypes->mapWithKeys(function (ServiceType $type) use ($clinic) {
                $offering = $clinic->services->firstWhere('service_type_id', $type->id);

                return [(string) $type->id => [
                    'duration_minutes' => $type->durationMinutes($offering?->duration_minutes),
                    'payment_modes' => $type->allowed_payment_modes ?? [],
                ]];
            })->all();
        }

        return view('admin.bookings.create', [
            'clinics' => $clinics,
            'clinic' => $clinic,
            'serviceTypes' => $serviceTypes,
            'paymentModes' => $paymentModes,
            'defaultPaymentMode' => $defaultPaymentMode,
            'gatewayReady' => $this->payments->isGatewayConfigured(),
            'dayOptions' => BookingDays::upcoming(21),
            'serviceFlags' => $serviceFlags,
            'doctorSlugs' => $clinic
                ? $clinic->doctors->mapWithKeys(fn (Doctor $doctor) => [(string) $doctor->id => $doctor->slug])->all()
                : [],
        ]);
    }

    public function lookupPatient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $normalized = User::normalizePhone($validated['phone']);

        if (strlen($normalized) < 11) {
            throw ValidationException::withMessages([
                'phone' => __('auth.invalid_phone'),
            ]);
        }

        $patient = User::query()->where('phone', $normalized)->first();

        if (! $patient) {
            return response()->json([
                'found' => false,
                'phone' => $normalized,
            ]);
        }

        return response()->json([
            'found' => true,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_id' => ['required', 'exists:clinics,id'],
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

        $clinic = Clinic::query()->findOrFail($validated['clinic_id']);
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

        $booking = $this->bookings->createForClinic($clinic, $validated, $request->user());
        Audit::log('booking.created', $booking, ['source' => 'admin']);

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', __('admin.bookings.created'));
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

        Audit::log('booking.'.$validated['action'], $booking, [
            'action' => $validated['action'],
            'status' => $booking->fresh()->status->value,
        ]);

        return back()->with('status', __('admin.bookings.updated'));
    }
}
