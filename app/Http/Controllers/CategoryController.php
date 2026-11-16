<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:terbaru,terpopuler,terlama'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $sort = $validated['sort'] ?? 'terbaru';

        $perPage = max(3, min(30, Setting::int('posts_per_page', (int) config('portal.pagination.posts'))));

        $posts = $category->posts()
            ->published()
            ->with(['category', 'author'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $query) use ($q): void {
                    $term = '%'.$q.'%';

                    $query->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('content', 'like', $term);
                });
            });

        match ($sort) {
            'terpopuler' => $posts->reorder()->orderByDesc('views')->orderByDesc('published_at')->orderByDesc('id'),
            'terlama' => $posts->reorder()->orderBy('published_at')->orderBy('id'),
            default => $posts->latestPublished(),
        };

        $posts = $posts->paginate($perPage)->withQueryString();

        $categories = Category::query()
            ->active()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('categories.show', [
            'category' => $category,
            'posts' => $posts,
            'categories' => $categories,
            'q' => $q,
            'sort' => $sort,
        ]);
    }
}
