<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LabOrderStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\LabOrder;
use App\Services\LabOrderManager;
use App\Support\Audit;
use App\Support\StatusTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabOrderController extends Controller implements HasMiddleware
{
    public function __construct(private LabOrderManager $orders) {}

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
            'status' => ['nullable', Rule::enum(LabOrderStatus::class)],
            'clinic' => ['nullable', 'integer', 'exists:clinics,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = LabOrder::query()
            ->with(['patient', 'clinic', 'items.labTest', 'items.labPackage'])
            ->when($filters['q'] ?? null, function ($builder, $term) {
                $builder->where(function ($inner) use ($term) {
                    $inner->whereLike('reference', "%{$term}%")
                        ->orWhereHas('patient', fn ($patient) => $patient
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

        $orders = (clone $query)
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->latest('scheduled_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.lab-orders.index', [
            'orders' => $orders,
            'statusCounts' => StatusTally::of($query, LabOrderStatus::cases()),
            'clinics' => Clinic::query()->orderBy('name_ar')->limit(200)->get(['id', 'name_ar', 'name_en']),
            'filters' => $filters,
        ]);
    }

    public function show(LabOrder $labOrder): View
    {
        $labOrder->load(['patient', 'clinic', 'address.city', 'items.labTest', 'items.labPackage']);

        return view('admin.lab-orders.show', [
            'order' => $labOrder,
        ]);
    }

    public function update(Request $request, LabOrder $labOrder): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['cancel'])],
        ]);

        if ($validated['action'] === 'cancel') {
            $this->orders->transition($labOrder, LabOrderStatus::Cancelled, $request->user());
            Audit::log('lab_order.cancelled', $labOrder, ['status' => LabOrderStatus::Cancelled->value]);
        }

        return back()->with('status', __('admin.lab_orders.cancelled'));
    }
}
