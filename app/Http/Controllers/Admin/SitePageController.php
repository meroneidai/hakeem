<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\SitePage;
use App\Support\Audit;
use App\Support\SafeHtml;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SitePageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSeoPages->value];
    }

    public function index(): View
    {
        $pages = SitePage::query()
            ->orderByDesc('is_system')
            ->orderBy('path')
            ->paginate(30);

        return view('admin.site-pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.site-pages.create', [
            'page' => new SitePage([
                'is_published' => true,
                'is_indexable' => true,
                'sitemap_priority' => 5,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = SitePage::query()->create($this->validated($request));
        Audit::created($page);

        return redirect()->route('admin.site-pages.index')->with('status', __('common.created_successfully'));
    }

    public function edit(SitePage $sitePage): View
    {
        return view('admin.site-pages.edit', ['page' => $sitePage]);
    }

    public function update(Request $request, SitePage $sitePage): RedirectResponse
    {
        $before = $sitePage->getOriginal();
        $sitePage->update($this->validated($request, $sitePage));
        Audit::updated($sitePage, $before);

        return redirect()->route('admin.site-pages.index')->with('status', __('common.updated_successfully'));
    }

    public function destroy(SitePage $sitePage): RedirectResponse
    {
        abort_if($sitePage->is_system, 403);

        Audit::deleted($sitePage);
        $sitePage->delete();

        return redirect()->route('admin.site-pages.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SitePage $page = null): array
    {
        $reserved = SitePage::reservedSlugs();

        $data = $request->validate([
            'heading_ar' => ['required', 'string', 'max:180'],
            'heading_en' => ['required', 'string', 'max:180'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                'alpha_dash',
                Rule::unique('site_pages', 'slug')->ignore($page),
                Rule::when(! $page?->is_system, [Rule::notIn($reserved)]),
            ],
            'intro_ar' => ['nullable', 'string', 'max:2000'],
            'intro_en' => ['nullable', 'string', 'max:2000'],
            'body_ar' => ['nullable', 'string', 'max:100000'],
            'body_en' => ['nullable', 'string', 'max:100000'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:320'],
            'meta_description_en' => ['nullable', 'string', 'max:320'],
            'sitemap_priority' => ['required', 'integer', 'min:1', 'max:10'],
            'is_published' => ['boolean'],
            'is_indexable' => ['boolean'],
        ]);

        if ($page?->is_system) {
            $data['slug'] = $page->slug;
            $data['path'] = $page->path;
            $data['is_system'] = true;
        } else {
            $data['slug'] = ($data['slug'] ?? null) ?: UniqueSlug::for($data['heading_en'], 'site_pages', 'slug', $page?->id);
            $data['path'] = SitePage::pathForSlug($data['slug']);
            $data['is_system'] = false;
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['is_indexable'] = $request->boolean('is_indexable');
        $data['body_ar'] = SafeHtml::sanitize($data['body_ar'] ?? '');
        $data['body_en'] = SafeHtml::sanitize($data['body_en'] ?? '');

        return $data;
    }
}
