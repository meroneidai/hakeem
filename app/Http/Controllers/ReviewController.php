<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless((int) $booking->patient_id === (int) $request->user()->id, 403);
        abort_unless($booking->status === BookingStatus::Completed, 404);
        abort_if($booking->review()->exists(), 404);

        $data = $request->validate([
            'overall' => ['required', 'integer', 'min:1', 'max:5'],
            'wait_time' => ['nullable', 'integer', 'min:1', 'max:5'],
            'staff' => ['nullable', 'integer', 'min:1', 'max:5'],
            'cleanliness' => ['nullable', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::query()->create([
            'booking_id' => $booking->id,
            'patient_id' => $booking->patient_id,
            'doctor_id' => $booking->doctor_id,
            'clinic_id' => $booking->clinic_id,
            'overall' => $data['overall'],
            'wait_time' => $data['wait_time'] ?? null,
            'staff' => $data['staff'] ?? null,
            'cleanliness' => $data['cleanliness'] ?? null,
            'body' => $data['body'] ?? null,
            'is_visible' => true,
        ]);

        return back()->with('status', __('reviews.thanks'));
    }
}
