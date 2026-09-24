<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfferApprovalStatus;
use App\Enums\OfferCategory;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Support\Audit;
use App\Support\PublicImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManagePromotions->value];
    }

    public function index(Request $request): View
    {
        $state = $request->string('state')->value();

        $promotions = Promotion::with(['specialty', 'serviceType', 'clinic'])
            ->when($state === 'running', fn ($query) => $query->running())
            ->when($state === 'pending', fn ($query) => $query->pendingApproval())
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'pendingCount' => Promotion::query()->pendingApproval()->count(),
            'state' => $state,
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.create', [
            'promotion' => new Promotion([
                'is_active' => true,
                'approval_status' => OfferApprovalStatus::Approved,
                'discount_type' => 'percentage',
                'category' => OfferCategory::Lab,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $promotion = Promotion::create([
            ...$this->validated($request),
            'created_by_user_id' => $request->user()->id,
            'approval_status' => OfferApprovalStatus::Approved,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        Audit::created($promotion);

        return redirect()->route('admin.promotions.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.edit', [
            'promotion' => $promotion,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $before = $promotion->getOriginal();
        $promotion->update([
            ...$this->validated($request, $promotion),
            'approval_status' => OfferApprovalStatus::Approved,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        Audit::updated($promotion, $before);

        return redirect()->route('admin.promotions.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function approve(Request $request, Promotion $promotion): RedirectResponse
    {
        $before = $promotion->getOriginal();

        $promotion->update([
            'approval_status' => OfferApprovalStatus::Approved,
            'is_active' => true,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        Audit::updated($promotion, $before);

        return back()->with('status', __('admin.promotions.approved'));
    }

    public function reject(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = $promotion->getOriginal();

        $promotion->update([
            'approval_status' => OfferApprovalStatus::Rejected,
            'is_active' => false,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        Audit::updated($promotion, $before);

        return back()->with('status', __('admin.promotions.rejected'));
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        Audit::deleted($promotion);
        PublicImage::delete($promotion->banner_image_path);
        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function formOptions(): array
    {
        return [
            'specialties' => Specialty::active()->ordered()->get(),
            'serviceTypes' => ServiceType::active()->ordered()->get(),
            'clinics' => Clinic::query()->orderBy('name_ar')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:160'],
            'title_en' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('promotions', 'slug')->ignore($promotion)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(OfferCategory::class)],
            'includes_ar' => ['nullable', 'string', 'max:2000'],
            'includes_en' => ['nullable', 'string', 'max:2000'],
            'conditions_ar' => ['nullable', 'string', 'max:2000'],
            'conditions_en' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed', 'custom'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999', 'required_unless:discount_type,custom'],
            'discount_details' => ['nullable', 'string', 'max:255', 'required_if:discount_type,custom'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'offer_price' => ['nullable', 'numeric', 'min:0'],
            'clinic_id' => ['nullable', 'exists:clinics,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'service_type_id' => ['nullable', 'exists:service_types,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'banner' => PublicImage::rules(),
        ]);

        if ($data['discount_type'] === 'percentage' && ($data['discount_value'] ?? 0) > 100) {
            $data['discount_value'] = 100;
        }

        if ($data['discount_type'] === 'custom') {
            $data['discount_value'] = null;
        }

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title_en']).'-'.Str::lower(Str::random(4));
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['banner']);
        $data['banner_image_path'] = PublicImage::store($request, 'banner', 'offers', $promotion?->banner_image_path);

        return $data;
    }
}
