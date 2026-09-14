<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Governorate;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageGeography->value];
    }

    public function index(Request $request): View
    {
        $cities = City::with('governorate')
            ->when($request->integer('governorate_id'), fn ($query, $id) => $query->where('governorate_id', $id))
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query
                ->where(fn ($q) => $q
                    ->whereLike('name_ar', "%{$term}%")
                    ->orWhereLike('name_en', "%{$term}%")))
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.cities.index', [
            'cities' => $cities,
            'governorates' => Governorate::ordered()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.cities.create', [
            'city' => new City(['is_active' => true, 'governorate_id' => $request->integer('governorate_id') ?: null]),
            'governorates' => Governorate::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $city = City::create($this->validated($request));

        Audit::created($city);

        return redirect()->route('admin.cities.index', ['governorate_id' => $city->governorate_id])
            ->with('status', __('common.created_successfully'));
    }

    public function edit(City $city): View
    {
        return view('admin.cities.edit', [
            'city' => $city,
            'governorates' => Governorate::ordered()->get(),
        ]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $before = $city->getOriginal();
        $city->update($this->validated($request, $city));

        Audit::updated($city, $before);

        return redirect()->route('admin.cities.index', ['governorate_id' => $city->governorate_id])
            ->with('status', __('common.updated_successfully'));
    }

    public function destroy(City $city): RedirectResponse
    {
        Audit::deleted($city);
        $city->delete();

        return redirect()->route('admin.cities.index')
            ->with('status', __('common.deleted_successfully'));
    }

    private function validated(Request $request, ?City $city = null): array
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'exists:governorates,id'],
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('cities', 'slug')->ignore($city)],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name_en']);
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
