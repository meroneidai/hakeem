<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MedicalArticleCategory;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\MedicalArticle;
use App\Models\Specialty;
use App\Support\Audit;
use App\Support\PublicImage;
use App\Support\SafeHtml;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalArticleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSeoPages->value];
    }

    public function index(Request $request): View
    {
        $articles = MedicalArticle::query()
            ->with('specialty')
            ->when($request->string('category')->value(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where(
                fn ($inner) => $inner->whereLike('title_ar', "%{$term}%")->orWhereLike('title_en', "%{$term}%")
            ))
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.articles.index', compact('articles'));
    }

    public function create(): View
    {
        return view('admin.articles.create', [
            'article' => new MedicalArticle(['is_published' => true, 'category' => MedicalArticleCategory::Prevention]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = MedicalArticle::create($this->validated($request));
        Audit::created($article);

        return redirect()->route('admin.articles.index')->with('status', __('common.created_successfully'));
    }

    public function edit(MedicalArticle $article): View
    {
        return view('admin.articles.edit', [
            'article' => $article,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, MedicalArticle $article): RedirectResponse
    {
        $before = $article->getOriginal();
        $article->update($this->validated($request, $article));
        Audit::updated($article, $before);

        return redirect()->route('admin.articles.index')->with('status', __('common.updated_successfully'));
    }

    public function destroy(MedicalArticle $article): RedirectResponse
    {
        Audit::deleted($article);
        PublicImage::delete($article->image_path);
        $article->delete();

        return redirect()->route('admin.articles.index')->with('status', __('common.deleted_successfully'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'specialties' => Specialty::query()->active()->ordered()->get(),
            'categories' => collect(MedicalArticleCategory::cases())
                ->mapWithKeys(fn (MedicalArticleCategory $category) => [$category->value => $category->label()])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?MedicalArticle $article = null): array
    {
        $data = $request->validate([
            'title_ar' => ['required', 'string', 'max:180'],
            'title_en' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('medical_articles', 'slug')->ignore($article)],
            'excerpt_ar' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'body_ar' => ['required', 'string', 'max:100000'],
            'body_en' => ['required', 'string', 'max:100000'],
            'category' => ['required', Rule::enum(MedicalArticleCategory::class)],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'image' => PublicImage::rules(),
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: UniqueSlug::for($data['title_en'], 'medical_articles', 'slug', $article?->id);
        $data['display_order'] ??= 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published']
            ? ($article?->published_at ?? now())
            : null;
        $data['specialty_id'] = filled($data['specialty_id'] ?? null) ? (int) $data['specialty_id'] : null;
        unset($data['image']);
        $data['image_path'] = PublicImage::store($request, 'image', 'articles', $article?->image_path);
        $data['body_ar'] = SafeHtml::sanitize($data['body_ar']);
        $data['body_en'] = SafeHtml::sanitize($data['body_en']);

        return $data;
    }
}
