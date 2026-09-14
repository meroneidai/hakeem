<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Support\Audit;
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
        $promotions = Promotion::with(['specialty', 'serviceType'])
            ->when($request->string('state')->value() === 'running', fn ($query) => $query->running())
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.promotions.create', [
            'promotion' => new Promotion([
                'is_active' => true,
                'discount_type' => 'percentage',
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
        $promotion->update($this->validated($request, $promotion));

        Audit::updated($promotion, $before);

        return redirect()->route('admin.promotions.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        Audit::deleted($promotion);
        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function formOptions(): array
    {
        return [
            'specialties' => Specialty::active()->ordered()->get(),
            'serviceTypes' => ServiceType::active()->ordered()->get(),
        ];
    }

    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:160'],
            'title_en' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('promotions', 'slug')->ignore($promotion)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed', 'custom'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999', 'required_unless:discount_type,custom'],
            'discount_details' => ['nullable', 'string', 'max:255', 'required_if:discount_type,custom'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'service_type_id' => ['nullable', 'exists:service_types,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
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

        return $data;
    }
}
