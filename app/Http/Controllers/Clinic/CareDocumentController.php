<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\CareDocumentType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CareDocument;
use App\Services\CareDocumentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CareDocumentController extends Controller
{
    use ResolvesClinicContext;

    public function __construct(private CareDocumentManager $documents) {}

    public function create(Request $request): View
    {
        $clinic = $this->clinic($request);
        $booking = Booking::query()
            ->where('clinic_id', $clinic->id)
            ->with(['patient', 'doctor', 'serviceType'])
            ->findOrFail($request->integer('booking'));

        $this->assertOwned($request, $booking);

        return view('clinic.care.create', [
            'clinic' => $clinic,
            'booking' => $booking,
            'types' => CareDocumentType::issuableAfterVisit(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clinic = $this->clinic($request);

        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'type' => ['required', Rule::enum(CareDocumentType::class)],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:4000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'medications' => ['nullable', 'array', 'max:20'],
            'medications.*.name' => ['required_with:medications', 'string', 'max:160'],
            'medications.*.dose' => ['nullable', 'string', 'max:80'],
            'medications.*.frequency' => ['nullable', 'string', 'max:80'],
            'medications.*.duration' => ['nullable', 'string', 'max:80'],
            'medications.*.notes' => ['nullable', 'string', 'max:240'],
        ]);

        $booking = Booking::query()
            ->where('clinic_id', $clinic->id)
            ->findOrFail($validated['booking_id']);

        $this->assertOwned($request, $booking);

        $type = CareDocumentType::from($validated['type']);

        if ($type === CareDocumentType::Prescription) {
            $request->validate([
                'medications' => ['required', 'array', 'min:1'],
                'medications.*.name' => ['required', 'string', 'max:160'],
            ]);
        }

        if ($type === CareDocumentType::SickLeave) {
            $request->validate([
                'valid_from' => ['required', 'date'],
                'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            ]);
        }

        if ($type === CareDocumentType::TreatmentPlan) {
            $request->validate([
                'body' => ['required', 'string', 'max:4000'],
            ]);
        }

        $medications = collect($validated['medications'] ?? [])
            ->filter(fn (array $row) => filled($row['name'] ?? null))
            ->values()
            ->all();

        $this->documents->issueForBooking($booking, $request->user(), [
            'type' => $type,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'payload' => [
                'medications' => $medications,
                'service' => $booking->serviceType?->name,
            ],
        ]);

        return redirect()
            ->route('clinic.queue.index', ['date' => $booking->scheduled_at?->toDateString()])
            ->with('status', __('clinic.care.issued'));
    }

    public function show(Request $request, CareDocument $careDocument): View
    {
        $this->assertOwned($request, $careDocument);

        $careDocument->load(['patient', 'doctor', 'clinic', 'booking']);

        return view('records.show', ['document' => $careDocument, 'clinicView' => true]);
    }
}
