<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->whereBelongsTo($request->user(), 'patient')
            ->with(['clinic', 'doctor.specialty', 'address.city', 'serviceType'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'status' => $booking->status->value,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'is_evaluation' => $booking->is_evaluation,
                'payment_status' => $booking->payment_status,
                'clinic' => $booking->clinic?->name,
                'doctor' => $booking->doctor?->name,
                'service' => $booking->serviceType?->name,
                'cancellable' => $booking->status->canTransitionTo(BookingStatus::Cancelled),
            ]);

        return response()->json(['data' => $bookings]);
    }

    public function destroy(Request $request, Booking $booking, BookingManager $bookings): JsonResponse
    {
        abort_unless((int) $booking->patient_id === (int) $request->user()->id, 404);

        $bookings->transition($booking, BookingStatus::Cancelled, $request->user());

        return response()->json([
            'ok' => true,
            'status' => $booking->fresh()->status->value,
        ]);
    }
}
