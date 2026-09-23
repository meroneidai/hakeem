<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class ReviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ModerateReviews->value];
    }

    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->with(['patient', 'doctor', 'clinic', 'booking'])
            ->when($request->filled('hidden'), fn ($query) => $query->where('is_visible', false))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'is_visible' => ['sometimes', 'boolean'],
            'clinic_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $review->getOriginal();

        if (array_key_exists('is_visible', $data)) {
            $review->is_visible = $request->boolean('is_visible');
        }

        if (array_key_exists('clinic_response', $data) && filled($data['clinic_response'])) {
            $review->clinic_response = $data['clinic_response'];
            $review->responded_at = now();
        }

        $review->save();

        Audit::updated($review, $before);

        return back()->with('status', __('common.updated_successfully'));
    }
}
