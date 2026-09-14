<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GovernorateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageGeography->value];
    }

    public function index(Request $request): View
    {
        $governorates = Governorate::withCount('cities')
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name_ar', "%{$term}%")
                    ->orWhereLike('name_en', "%{$term}%")))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.governorates.index', compact('governorates'));
    }

    public function create(): View
    {
        return view('admin.governorates.create', ['governorate' => new Governorate(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $governorate = Governorate::create($this->validated($request));

        Audit::created($governorate);

        return redirect()->route('admin.governorates.index')
            ->with('status', __('common.created_successfully'));
    }

    public function edit(Governorate $governorate): View
    {
        return view('admin.governorates.edit', compact('governorate'));
    }

    public function update(Request $request, Governorate $governorate): RedirectResponse
    {
        $before = $governorate->getOriginal();
        $governorate->update($this->validated($request, $governorate));

        Audit::updated($governorate, $before);

        return redirect()->route('admin.governorates.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(Governorate $governorate): RedirectResponse
    {
        Audit::deleted($governorate);
        $governorate->delete();

        return redirect()->route('admin.governorates.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function validated(Request $request, ?Governorate $governorate = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('governorates', 'slug')->ignore($governorate)],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
