<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Services\SeoPageGenerator;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeoPageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSeoPages->value];
    }

    public function index(Request $request): View
    {
        $pages = SeoPage::with(['governorate', 'city', 'specialty', 'serviceType'])
            ->when($request->string('page_type')->value(), fn ($query, $type) => $query->where('page_type', $type))
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->whereLike('path', "%{$term}%"))
            ->orderByDesc('sitemap_priority')
            ->orderBy('path')
            ->paginate(30)
            ->withQueryString();

        return view('admin.seo-pages.index', [
            'pages' => $pages,
            'counts' => SeoPage::selectRaw('page_type, count(*) as total')
                ->groupBy('page_type')
                ->pluck('total', 'page_type'),
        ]);
    }

    public function edit(SeoPage $seoPage): View
    {
        return view('admin.seo-pages.edit', ['page' => $seoPage->load(['governorate', 'city', 'specialty', 'serviceType'])]);
    }

    public function update(Request $request, SeoPage $seoPage): RedirectResponse
    {
        $data = $request->validate([
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:320'],
            'meta_description_en' => ['nullable', 'string', 'max:320'],
            'h1_ar' => ['nullable', 'string', 'max:255'],
            'h1_en' => ['nullable', 'string', 'max:255'],
            'intro_content_ar' => ['nullable', 'string', 'max:5000'],
            'intro_content_en' => ['nullable', 'string', 'max:5000'],
            'sitemap_priority' => ['required', 'integer', 'min:1', 'max:10'],
            'is_indexable' => ['boolean'],
        ]);

        $data['is_indexable'] = $request->boolean('is_indexable');

        $before = $seoPage->getOriginal();
        $seoPage->update($data);

        Audit::updated($seoPage, $before);

        return redirect()->route('admin.seo-pages.index')
            ->with('status', __('common.updated_successfully'));
    }

    public function generate(SeoPageGenerator $generator): RedirectResponse
    {
        $created = $generator->generateMissing();

        Audit::log('seo_pages.generated', changes: ['created' => $created]);

        return back()->with('status', __('admin.seo.generated', ['count' => $created]));
    }

    public function destroy(SeoPage $seoPage): RedirectResponse
    {
        Audit::deleted($seoPage);
        $seoPage->delete();

        return redirect()->route('admin.seo-pages.index')
            ->with('status', __('common.deleted_successfully'));
    }
}
