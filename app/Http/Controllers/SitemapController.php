<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->published()
            ->select(['slug', 'updated_at'])
            ->latestPublished()
            ->limit(2000)
            ->get();

        $categories = Category::query()
            ->active()
            ->select(['slug', 'updated_at'])
            ->orderBy('sort_order')
            ->get();

        $bulletins = Bulletin::query()
            ->published()
            ->select(['id', 'updated_at'])
            ->orderByDesc('published_at')
            ->limit(1000)
            ->get();

        $staticLastmod = [
            'profil' => $this->lastModified(Page::query()
                ->whereIn('slug', ['tentang-supd1', 'tugas-dan-fungsi'])
                ->max('updated_at')),
            'galeri' => $this->lastModified(GalleryItem::query()->max('updated_at')),
            'struktur' => $this->lastModified(OrganizationalUnit::query()->max('updated_at')),
        ];

        return response()
            ->view('sitemap', [
                'posts' => $posts,
                'categories' => $categories,
                'bulletins' => $bulletins,
                'staticLastmod' => $staticLastmod,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function lastModified(?string $timestamp): ?string
    {
        return $timestamp ? Carbon::parse($timestamp)->toAtomString() : null;
    }
}
