<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecialtyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSpecialties->value];
    }

    public function index(Request $request): View
    {
        $specialties = Specialty::query()
            ->when($request->string('category')->trim()->value(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name_ar', "%{$term}%")
                    ->orWhereLike('name_en', "%{$term}%")))
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.specialties.index', compact('specialties'));
    }

    public function create(): View
    {
        return view('admin.specialties.create', [
            'specialty' => new Specialty(['is_active' => true, 'category' => 'general']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $specialty = Specialty::create($this->validated($request));

        Audit::created($specialty);

        return redirect()->route('admin.specialties.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(Specialty $specialty): View
    {
        return view('admin.specialties.edit', compact('specialty'));
    }

    public function update(Request $request, Specialty $specialty): RedirectResponse
    {
        $before = $specialty->getOriginal();
        $specialty->update($this->validated($request, $specialty));

        Audit::updated($specialty, $before);

        return redirect()->route('admin.specialties.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(Specialty $specialty): RedirectResponse
    {
        Audit::deleted($specialty);
        $specialty->delete();

        return redirect()->route('admin.specialties.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function validated(Request $request, ?Specialty $specialty = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('specialties', 'slug')->ignore($specialty)],
            'category' => ['required', Rule::in(Specialty::CATEGORIES)],
            'description_ar' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:64'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
