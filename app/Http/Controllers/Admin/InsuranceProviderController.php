<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Support\Audit;
use App\Support\PublicImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InsuranceProviderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageInsuranceProviders->value];
    }

    public function index(Request $request): View
    {
        $providers = InsuranceProvider::query()
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query
                ->where(fn ($inner) => $inner
                    ->whereLike('name_ar', "%{$term}%")
                    ->orWhereLike('name_en', "%{$term}%")))
            ->withCount('clinicServices')
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.insurance-providers.index', compact('providers'));
    }

    public function create(): View
    {
        return view('admin.insurance-providers.create', [
            'provider' => new InsuranceProvider(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $provider = InsuranceProvider::create($this->validated($request));

        Audit::created($provider);

        return redirect()->route('admin.insurance-providers.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(InsuranceProvider $insuranceProvider): View
    {
        return view('admin.insurance-providers.edit', ['provider' => $insuranceProvider]);
    }

    public function update(Request $request, InsuranceProvider $insuranceProvider): RedirectResponse
    {
        $before = $insuranceProvider->getOriginal();
        $insuranceProvider->update($this->validated($request, $insuranceProvider));

        Audit::updated($insuranceProvider, $before);

        return redirect()->route('admin.insurance-providers.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(InsuranceProvider $insuranceProvider): RedirectResponse
    {
        Audit::deleted($insuranceProvider);
        PublicImage::delete($insuranceProvider->image_path);
        $insuranceProvider->delete();

        return redirect()->route('admin.insurance-providers.index')
            ->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InsuranceProvider $provider = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('insurance_providers', 'slug')->ignore($provider)],
            'hotline' => ['nullable', 'string', 'max:32'],
            'notes_ar' => ['nullable', 'string', 'max:2000'],
            'notes_en' => ['nullable', 'string', 'max:2000'],
            'image' => PublicImage::rules(),
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);
        $data['image_path'] = PublicImage::store($request, 'image', 'insurance-providers', $provider?->image_path);

        return $data;
    }
}
