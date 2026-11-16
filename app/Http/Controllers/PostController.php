<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\PortalAnalytics;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:120'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $categorySlug = $validated['kategori'] ?? null;
        $perPage = max(3, min(30, Setting::int('posts_per_page', (int) config('portal.pagination.posts'))));
        $popularCount = max(1, min(10, Setting::int('popular_posts_count', 5)));

        $posts = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $query) use ($q): void {
                    $term = '%'.$q.'%';

                    $query->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('content', 'like', $term);
                });
            })
            ->when($categorySlug, function (Builder $query) use ($categorySlug): void {
                $query->whereHas('category', fn (Builder $query) => $query->where('slug', $categorySlug));
            })
            ->latestPublished()
            ->paginate($perPage)
            ->withQueryString();

        $popular = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit($popularCount)
            ->get();

        $categories = Category::query()
            ->active()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('posts.index', [
            'posts' => $posts,
            'popular' => $popular,
            'categories' => $categories,
            'q' => $q,
            'categorySlug' => $categorySlug,
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->isPublished(), 404);

        return $this->renderPost($post, preview: false);
    }

    /**
     * Pratinjau draf untuk admin/redaksi: isi draf terlihat apa adanya
     * tanpa mempublikasikan dan tanpa menambah hitungan views.
     */
    public function preview(Post $post): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return redirect()->route('filament.admin.auth.login');
        }

        abort_unless($user->canAccessPanel(Filament::getPanel('admin')), 403);

        return $this->renderPost($post, preview: true);
    }

    private function renderPost(Post $post, bool $preview): View
    {
        if (! $preview) {
            abort_unless($post->isPublished(), 404);
        }

        $post->load(['category', 'author']);

        if (! $preview) {
            $post->increment('views');
            PortalAnalytics::recordVisit($post);
        }

        $related = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->getKey())
            ->latestPublished()
            ->limit(3)
            ->get();

        [$previous, $next] = $this->adjacentPosts($post);

        $description = $post->excerpt ?: Str::limit(strip_tags((string) $post->content), 160);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $description,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? 'Redaksi '.Setting::get('site_short_name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => Setting::get('site_directorate'),
            ],
            'mainEntityOfPage' => route('posts.show', $post),
        ];

        if ($post->featured_image) {
            $jsonLd['image'] = [$post->featuredImageUrl()];
        }

        return view('posts.show', [
            'post' => $post,
            'related' => $related,
            'previous' => $previous,
            'next' => $next,
            'description' => $description,
            'jsonLd' => $preview ? null : $jsonLd,
            'preview' => $preview,
        ]);
    }

    /**
     * Berita sebelum (lebih lama) dan sesudah (lebih baru) berdasarkan urutan
     * publikasi yang sama dengan daftar berita. Draf dan berita terjadwal
     * tidak pernah muncul karena memakai scope published().
     *
     * @return array{0: ?Post, 1: ?Post}
     */
    private function adjacentPosts(Post $post): array
    {
        $anchor = $post->published_at ?? $post->created_at;

        $previous = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->whereKeyNot($post->getKey())
            ->where(function (Builder $query) use ($anchor, $post): void {
                $query->whereRaw('COALESCE(published_at, created_at) < ?', [$anchor])
                    ->orWhere(function (Builder $query) use ($anchor, $post): void {
                        $query->whereRaw('COALESCE(published_at, created_at) = ?', [$anchor])
                            ->where('id', '<', $post->getKey());
                    });
            })
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $next = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->whereKeyNot($post->getKey())
            ->where(function (Builder $query) use ($anchor, $post): void {
                $query->whereRaw('COALESCE(published_at, created_at) > ?', [$anchor])
                    ->orWhere(function (Builder $query) use ($anchor, $post): void {
                        $query->whereRaw('COALESCE(published_at, created_at) = ?', [$anchor])
                            ->where('id', '>', $post->getKey());
                    });
            })
            ->orderByRaw('COALESCE(published_at, created_at) ASC')
            ->orderBy('id')
            ->first();

        return [$previous, $next];
    }
}
