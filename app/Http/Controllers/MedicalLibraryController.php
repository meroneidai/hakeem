<?php

namespace App\Http\Controllers;

use App\Enums\MedicalArticleCategory;
use App\Models\MedicalArticle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:32'],
        ]);

        $articles = MedicalArticle::query()
            ->published()
            ->with('specialty')
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('library.index', [
            'articles' => $articles,
            'categories' => MedicalArticleCategory::cases(),
            'filters' => $filters,
        ]);
    }

    public function show(MedicalArticle $article): View
    {
        abort_unless($article->is_published && $article->published_at?->lte(now()), 404);

        $article->load('specialty');

        $related = MedicalArticle::query()
            ->published()
            ->whereKeyNot($article->id)
            ->when($article->specialty_id, fn ($query) => $query->where('specialty_id', $article->specialty_id))
            ->ordered()
            ->limit(4)
            ->get();

        return view('library.show', compact('article', 'related'));
    }
}
