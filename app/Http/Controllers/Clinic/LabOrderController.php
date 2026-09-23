<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\LabOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Services\LabOrderManager;
use App\Support\StatusTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabOrderController extends Controller
{
    use ResolvesClinicContext;

    public function __construct(private LabOrderManager $orders) {}

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        $request->merge([
            'status' => $request->filled('status') ? $request->input('status') : null,
        ]);

        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(LabOrderStatus::class)],
        ]);

        $date = $filters['date'] ?? now()->toDateString();

        $dayQuery = LabOrder::query()
            ->where('clinic_id', $clinic->id)
            ->onDate($date);

        $orders = (clone $dayQuery)
            ->with(['patient', 'address', 'patientAddress', 'items.labTest', 'items.labPackage'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        $statusCounts = StatusTally::of($dayQuery, LabOrderStatus::cases());

        return view('clinic.lab-orders.index', [
            'clinic' => $clinic,
            'orders' => $orders,
            'filters' => $filters + ['date' => $date],
            'pendingCount' => $statusCounts[LabOrderStatus::Pending->value] ?? 0,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Request $request, LabOrder $labOrder): View
    {
        $this->assertOwned($request, $labOrder);

        $labOrder->load(['patient', 'address', 'patientAddress', 'items.labTest', 'items.labPackage', 'careDocuments']);

        return view('clinic.lab-orders.show', [
            'clinic' => $this->clinic($request),
            'order' => $labOrder,
        ]);
    }

    public function update(Request $request, LabOrder $labOrder): RedirectResponse
    {
        $this->assertOwned($request, $labOrder);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'complete', 'cancel', 'mark_paid', 'mark_unpaid'])],
            'results' => ['nullable', 'array'],
            'results.*.value' => ['nullable', 'string', 'max:80'],
            'results.*.unit' => ['nullable', 'string', 'max:32'],
            'results.*.flag' => ['nullable', 'string', 'max:16'],
            'results.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        match ($validated['action']) {
            'confirm' => $this->orders->transition($labOrder, LabOrderStatus::Confirmed, $request->user()),
            'complete' => $this->orders->transition(
                $labOrder,
                LabOrderStatus::Completed,
                $request->user(),
                $validated['results'] ?? [],
            ),
            'cancel' => $this->orders->transition($labOrder, LabOrderStatus::Cancelled, $request->user()),
            'mark_paid' => $this->orders->markPayment($labOrder, 'paid'),
            'mark_unpaid' => $this->orders->markPayment($labOrder, 'unpaid'),
        };

        return back()->with('status', __('clinic.queue.updated'));
    }
}
