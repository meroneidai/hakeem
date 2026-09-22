<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LabTestCategory;
use App\Enums\Permission;
use App\Enums\SampleType;
use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Support\Audit;
use App\Support\PublicImage;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabTestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageLabCatalog->value];
    }

    public function index(Request $request): View
    {
        $tests = LabTest::query()
            ->when($request->string('category')->value(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(
                fn ($inner) => $inner->whereLike('name_ar', "%{$term}%")->orWhereLike('name_en', "%{$term}%")
            ))
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.labs.tests.index', compact('tests'));
    }

    public function create(): View
    {
        return view('admin.labs.tests.create', [
            'test' => new LabTest(['is_active' => true, 'sample_type' => SampleType::Blood, 'category' => LabTestCategory::Blood]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $test = LabTest::create($this->validated($request));
        Audit::created($test);

        return redirect()->route('admin.lab-tests.index')->with('status', __('common.created_successfully'));
    }

    public function edit(LabTest $labTest): View
    {
        return view('admin.labs.tests.edit', ['test' => $labTest]);
    }

    public function update(Request $request, LabTest $labTest): RedirectResponse
    {
        $before = $labTest->getOriginal();
        $labTest->update($this->validated($request, $labTest));
        Audit::updated($labTest, $before);

        return redirect()->route('admin.lab-tests.index')->with('status', __('common.updated_successfully'));
    }

    public function destroy(LabTest $labTest): RedirectResponse
    {
        Audit::deleted($labTest);
        PublicImage::delete($labTest->image_path);
        $labTest->delete();

        return redirect()->route('admin.lab-tests.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LabTest $test = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('lab_tests', 'slug')->ignore($test)],
            'category' => ['required', Rule::enum(LabTestCategory::class)],
            'sample_type' => ['required', Rule::enum(SampleType::class)],
            'description_ar' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'measures_ar' => ['nullable', 'string', 'max:2000'],
            'measures_en' => ['nullable', 'string', 'max:2000'],
            'preparation_ar' => ['nullable', 'string', 'max:2000'],
            'preparation_en' => ['nullable', 'string', 'max:2000'],
            'contains_ar' => ['nullable', 'string', 'max:2000'],
            'contains_en' => ['nullable', 'string', 'max:2000'],
            'fasting_hours' => ['nullable', 'integer', 'min:0', 'max:24'],
            'turnaround_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'suggested_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'image' => PublicImage::rules(),
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: UniqueSlug::for($data['name_en'], 'lab_tests', 'slug', $test?->id);
        $data['display_order'] ??= 0;
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);
        $data['image_path'] = PublicImage::store($request, 'image', 'lab-tests', $test?->image_path);

        return $data;
    }
}
