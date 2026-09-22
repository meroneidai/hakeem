<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\LabOrderStatus;
use App\Models\Booking;
use App\Models\LabOrder;
use App\Services\BookingManager;
use App\Services\LabOrderManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->whereBelongsTo($request->user(), 'patient')
            ->with(['clinic', 'doctor.specialty', 'address.city', 'serviceType', 'review', 'promotion'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(12);

        $labOrders = LabOrder::query()
            ->where('patient_id', $request->user()->id)
            ->with(['clinic', 'address', 'items.labTest', 'items.labPackage'])
            ->orderByDesc('scheduled_at')
            ->limit(20)
            ->get();

        return view('appointments.index', compact('bookings', 'labOrders'));
    }

    public function destroy(Request $request, Booking $booking, BookingManager $bookings): RedirectResponse
    {
        abort_unless((int) $booking->patient_id === (int) $request->user()->id, 404);

        $bookings->transition($booking, BookingStatus::Cancelled, $request->user());

        return back()->with('status', __('booking.cancelled'));
    }

    public function destroyLabOrder(Request $request, LabOrder $labOrder, LabOrderManager $orders): RedirectResponse
    {
        abort_unless((int) $labOrder->patient_id === (int) $request->user()->id, 404);

        $orders->transition($labOrder, LabOrderStatus::Cancelled, $request->user());

        return back()->with('status', __('labs.checkout.cancelled'));
    }
}
