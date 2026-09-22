<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Support\Audit;
use App\Support\PublicImage;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LabPackageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageLabCatalog->value];
    }

    public function index(): View
    {
        $packages = LabPackage::query()->withCount('tests')->ordered()->paginate(20);

        return view('admin.labs.packages.index', compact('packages'));
    }

    public function create(): View
    {
        return view('admin.labs.packages.create', $this->formData(new LabPackage(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $package = LabPackage::create($this->validated($request));
        $package->tests()->sync($request->input('test_ids', []));
        Audit::created($package);

        return redirect()->route('admin.lab-packages.index')->with('status', __('common.created_successfully'));
    }

    public function edit(LabPackage $labPackage): View
    {
        $labPackage->load('tests');

        return view('admin.labs.packages.edit', $this->formData($labPackage));
    }

    public function update(Request $request, LabPackage $labPackage): RedirectResponse
    {
        $before = $labPackage->getOriginal();
        $labPackage->update($this->validated($request, $labPackage));
        $labPackage->tests()->sync($request->input('test_ids', []));
        Audit::updated($labPackage, $before);

        return redirect()->route('admin.lab-packages.index')->with('status', __('common.updated_successfully'));
    }

    public function destroy(LabPackage $labPackage): RedirectResponse
    {
        Audit::deleted($labPackage);
        PublicImage::delete($labPackage->image_path);
        $labPackage->delete();

        return redirect()->route('admin.lab-packages.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(LabPackage $package): array
    {
        return [
            'package' => $package,
            'tests' => LabTest::query()->active()->ordered()->get(),
            'selectedTestIds' => $package->exists ? $package->tests->pluck('id')->all() : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?LabPackage $package = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('lab_packages', 'slug')->ignore($package)],
            'description_ar' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'includes_ar' => ['nullable', 'string', 'max:2000'],
            'includes_en' => ['nullable', 'string', 'max:2000'],
            'conditions_ar' => ['nullable', 'string', 'max:2000'],
            'conditions_en' => ['nullable', 'string', 'max:2000'],
            'preparation_ar' => ['nullable', 'string', 'max:2000'],
            'preparation_en' => ['nullable', 'string', 'max:2000'],
            'original_price' => ['required', 'numeric', 'min:0'],
            'package_price' => ['required', 'numeric', 'min:0'],
            'image' => PublicImage::rules(),
            'test_ids' => ['required', 'array', 'min:2'],
            'test_ids.*' => ['exists:lab_tests,id'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        unset($data['test_ids']);
        unset($data['image']);
        $data['slug'] = ($data['slug'] ?? null) ?: UniqueSlug::for($data['name_en'], 'lab_packages', 'slug', $package?->id);
        $data['display_order'] ??= 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['image_path'] = PublicImage::store($request, 'image', 'lab-packages', $package?->image_path);

        return $data;
    }
}
