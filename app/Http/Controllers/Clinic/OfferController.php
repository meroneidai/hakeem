<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\OfferApprovalStatus;
use App\Enums\OfferCategory;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Support\PublicImage;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OfferController extends Controller
{
    use ResolvesClinicContext;

    public function index(Request $request): View
    {
        $clinic = $this->clinic($request);

        return view('clinic.offers.index', [
            'clinic' => $clinic,
            'offers' => $clinic->promotions()->latest('starts_at')->get(),
            'access' => $this->access($request),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManage($request);

        return view('clinic.offers.create', [
            'clinic' => $this->clinic($request),
            'offer' => new Promotion([
                'is_active' => false,
                'approval_status' => OfferApprovalStatus::Pending,
                'category' => OfferCategory::Lab,
                'discount_type' => 'percentage',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $this->clinic($request)->promotions()->create($this->validated($request));

        return redirect()->route('clinic.offers.index')->with('status', __('clinic.offers.submitted'));
    }

    public function edit(Request $request, Promotion $offer): View
    {
        $this->assertOwned($request, $offer);
        $this->authorizeManage($request);

        return view('clinic.offers.edit', [
            'clinic' => $this->clinic($request),
            'offer' => $offer,
        ]);
    }

    public function update(Request $request, Promotion $offer): RedirectResponse
    {
        $this->assertOwned($request, $offer);
        $this->authorizeManage($request);

        $offer->update($this->validated($request, $offer));

        return redirect()->route('clinic.offers.index')->with('status', __('clinic.offers.resubmitted'));
    }

    public function destroy(Request $request, Promotion $offer): RedirectResponse
    {
        $this->assertOwned($request, $offer);
        $this->authorizeManage($request);
        PublicImage::delete($offer->banner_image_path);
        $offer->delete();

        return redirect()->route('clinic.offers.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Promotion $offer = null): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:160'],
            'title_en' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(OfferCategory::class)],
            'description_ar' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'includes_ar' => ['nullable', 'string', 'max:2000'],
            'includes_en' => ['nullable', 'string', 'max:2000'],
            'conditions_ar' => ['nullable', 'string', 'max:2000'],
            'conditions_en' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed', 'custom'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'offer_price' => ['nullable', 'numeric', 'min:0'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_featured' => ['boolean'],
            'banner' => PublicImage::rules(),
        ]);

        $data['slug'] = UniqueSlug::for($data['title_en'], 'promotions', 'slug', $offer?->id);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = false;
        $data['approval_status'] = OfferApprovalStatus::Pending;
        $data['reviewed_by_user_id'] = null;
        $data['reviewed_at'] = null;
        $data['rejection_reason'] = null;
        $data['created_by_user_id'] = $request->user()->id;
        unset($data['banner']);
        $data['banner_image_path'] = PublicImage::store($request, 'banner', 'offers', $offer?->banner_image_path);

        return $data;
    }
}
