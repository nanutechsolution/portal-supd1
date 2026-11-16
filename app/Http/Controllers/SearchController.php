<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pencarian global: berita, buletin, dan halaman statis/portal.
 * Pencarian berita per rubrik tetap tersedia di /berita?q=...
 */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));

        $posts = collect();
        $bulletins = collect();
        $pages = collect();
        $postCount = 0;
        $bulletinCount = 0;

        if ($q !== '') {
            $term = '%'.$q.'%';

            $postQuery = Post::query()
                ->published()
                ->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('content', 'like', $term);
                });

            $postCount = (clone $postQuery)->count();

            $posts = (clone $postQuery)
                ->with(['category', 'author'])
                ->latestPublished()
                ->limit(9)
                ->get();

            $bulletinQuery = Bulletin::query()
                ->published()
                ->where(function (Builder $query) use ($term, $q): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('edition_number', 'like', $term);

                    if (ctype_digit($q)) {
                        $query->orWhere('year', (int) $q);
                    }
                });

            $bulletinCount = (clone $bulletinQuery)->count();

            $bulletins = (clone $bulletinQuery)
                ->orderByDesc('published_at')
                ->limit(6)
                ->get();

            $pages = $this->matchingPages($q);
        }

        $categories = Category::query()
            ->active()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('search.index', [
            'q' => $q,
            'posts' => $posts,
            'bulletins' => $bulletins,
            'pages' => $pages,
            'postCount' => $postCount,
            'bulletinCount' => $bulletinCount,
            'categories' => $categories,
            'suggestions' => $this->suggestions($categories),
            'totalResults' => $postCount + $bulletinCount + $pages->count(),
        ]);
    }

    /**
     * @return Collection<int, array{title: string, description: string, url: string, label: string}>
     */
    private function matchingPages(string $q): Collection
    {
        $static = collect([
            [
                'title' => 'Profil Direktorat',
                'description' => 'Tentang SUPD I beserta tugas dan fungsi direktorat.',
                'url' => route('pages.profil'),
                'keywords' => 'profil tentang tugas fungsi direktorat sejarah',
                'label' => 'Halaman',
            ],
            [
                'title' => 'Struktur Organisasi',
                'description' => 'Bagan organisasi dan rincian unit kerja SUPD I.',
                'url' => route('structure.index'),
                'keywords' => 'struktur organisasi unit kerja bagan pejabat',
                'label' => 'Halaman',
            ],
            [
                'title' => 'Arsip Berita',
                'description' => 'Arsip publikasi per tahun, bulan, rubrik, dan penulis.',
                'url' => route('archive.index'),
                'keywords' => 'arsip berita tahun bulan rubrik penulis',
                'label' => 'Halaman',
            ],
            [
                'title' => 'Buletin',
                'description' => 'Kumpulan buletin dan publikasi berkala untuk diunduh.',
                'url' => route('bulletins.index'),
                'keywords' => 'buletin unduh pdf edisi publikasi berkala',
                'label' => 'Halaman',
            ],
            [
                'title' => 'Galeri',
                'description' => 'Dokumentasi kegiatan direktorat dalam gambar.',
                'url' => route('galleries.index'),
                'keywords' => 'galeri foto dokumentasi gambar kegiatan',
                'label' => 'Halaman',
            ],
            [
                'title' => 'Kontak',
                'description' => 'Formulir pesan dan informasi kontak redaksi.',
                'url' => route('contact.index'),
                'keywords' => 'kontak hubungi formulir pengaduan email telepon alamat',
                'label' => 'Halaman',
            ],
        ])->filter(fn (array $page): bool => Str::contains(
            $page['title'].' '.$page['description'].' '.$page['keywords'],
            $q,
            ignoreCase: true,
        ))->values();

        $term = '%'.$q.'%';

        $pageContentMatches = Page::query()
            ->whereIn('slug', ['tentang-supd1', 'tugas-dan-fungsi'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('content', 'like', $term)
                    ->orWhere('meta_description', 'like', $term);
            })
            ->get()
            ->map(fn (Page $page): array => [
                'title' => $page->title,
                'description' => Str::limit(strip_tags((string) $page->content), 160),
                'url' => route('pages.profil').'#'.($page->slug === 'tentang-supd1' ? 'tentang-heading' : 'tugas-heading'),
                'label' => 'Halaman',
            ]);

        return $static
            ->concat($pageContentMatches)
            ->unique(fn (array $page): string => $page['url'].'|'.$page['title'])
            ->values();
    }

    /**
     * @return Collection<int, array{label: string, url: string}>
     */
    private function suggestions(Collection $categories): Collection
    {
        $categorySuggestions = $categories->take(4)->map(fn (Category $category): array => [
            'label' => 'Rubrik '.$category->name,
            'url' => route('categories.show', $category),
        ]);

        $pageSuggestions = collect([
            ['label' => 'Arsip Berita', 'url' => route('archive.index')],
            ['label' => 'Struktur Organisasi', 'url' => route('structure.index')],
            ['label' => 'Kontak', 'url' => route('contact.index')],
        ]);

        return $categorySuggestions->concat($pageSuggestions)->values();
    }
}
