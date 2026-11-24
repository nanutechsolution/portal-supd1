<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $homepageCount = max(3, min(24, Setting::int('homepage_news_count', 6)));
        $popularCount = max(1, min(10, Setting::int('popular_posts_count', 5)));

        $headline = Post::query()
            ->published()
            ->featured()
            ->with(['category', 'author'])
            ->latestPublished()
            ->first()
            ?? Post::query()
                ->published()
                ->with(['category', 'author'])
                ->latestPublished()
                ->first();

        $secondary = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->when($headline, fn (Builder $query) => $query->whereKeyNot($headline->getKey()))
            ->latestPublished()
            ->limit(4)
            ->get();

        $latest = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->latestPublished()
            ->limit($homepageCount)
            ->get();

        $ticker = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->orderByDesc('is_breaking')
            ->latestPublished()
            ->limit(5)
            ->get();

        $popular = Post::query()
            ->published()
            ->with(['category', 'author'])
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit($popularCount)
            ->get();

        $bulletins = Bulletin::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $gallery = GalleryItem::query()
            ->latestFirst()
            ->limit(6)
            ->get();

        $categorySections = Category::query()
            ->active()
            ->with([
                'posts' => fn ($query) => $query
                    ->published()
                    ->with(['category', 'author'])
                    ->latestPublished()
                    ->limit(4),
            ])
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('home', [
            'headline' => $headline,
            'secondary' => $secondary,
            'latest' => $latest,
            'ticker' => $ticker,
            'popular' => $popular,
            'bulletins' => $bulletins,
            'gallery' => $gallery,
            'categorySections' => $categorySections,
        ]);
    }
}
