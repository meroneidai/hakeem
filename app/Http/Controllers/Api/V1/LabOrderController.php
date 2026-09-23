<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = LabOrder::query()
            ->where('patient_id', $request->user()->id)
            ->with(['clinic', 'items.labTest', 'items.labPackage'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (LabOrder $order) => $this->payload($order));

        return response()->json(['data' => $orders]);
    }

    public function show(Request $request, LabOrder $labOrder): JsonResponse
    {
        abort_unless((int) $labOrder->patient_id === (int) $request->user()->id, 404);

        $labOrder->load(['clinic', 'address.city', 'items.labTest', 'items.labPackage']);

        return response()->json(['data' => $this->payload($labOrder)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LabOrder $order): array
    {
        return [
            'id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status->value,
            'total' => (float) $order->total,
            'scheduled_at' => $order->scheduled_at?->toIso8601String(),
            'clinic' => $order->clinic?->name,
            'collection_mode' => $order->collection_mode?->value,
            'home_address' => $order->patient_home_address,
            'lat' => $order->latitude !== null ? (float) $order->latitude : null,
            'lng' => $order->longitude !== null ? (float) $order->longitude : null,
            'url' => route('labs.orders.show', $order),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->catalogItem()?->name,
                'result_value' => $item->result_value,
                'result_note' => $item->result_note,
            ])->values()->all(),
        ];
    }
}
