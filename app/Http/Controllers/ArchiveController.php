<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Arsip berita yang sudah dipublikasikan, dikelompokkan per tahun dan bulan
 * publikasi. Dapat disaring per rubrik (kategori) dan per penulis.
 */
final class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'kategori' => ['nullable', 'string', 'max:120'],
            'penulis' => ['nullable', 'integer', 'min:1'],
        ]);

        $year = isset($validated['tahun']) ? (int) $validated['tahun'] : null;
        $month = isset($validated['bulan']) ? (int) $validated['bulan'] : null;

        $category = null;

        if (filled($validated['kategori'] ?? null)) {
            $category = Category::query()
                ->where('slug', $validated['kategori'])
                ->where('is_active', true)
                ->first();

            abort_if($category === null, 404);
        }

        $author = null;

        if (filled($validated['penulis'] ?? null)) {
            $author = User::query()->find((int) $validated['penulis']);

            abort_if($author === null, 404);
        }

        $applyFilters = function (Builder $query) use ($category, $author): void {
            $query
                ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
                ->when($author, fn (Builder $query) => $query->where('user_id', $author->id));
        };

        // Satu query agregat untuk seluruh pengelompokan arsip (tahun+bulan),
        // mengikuti filter rubrik/penulis yang sedang aktif.
        $periods = Post::query()
            ->published()
            ->whereNotNull('published_at')
            ->tap($applyFilters)
            ->selectRaw('substr(published_at, 1, 4) AS tahun, substr(published_at, 6, 2) AS bulan, COUNT(*) AS total')
            ->groupBy('tahun', 'bulan')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get()
            ->map(fn (object $row): array => [
                'tahun' => (int) $row->tahun,
                'bulan' => (int) $row->bulan,
                'total' => (int) $row->total,
            ]);

        $archives = $periods->groupBy('tahun');
        $totalByYear = $archives->map(fn ($months): int => (int) $months->sum('total'));
        $totalAll = (int) $periods->sum('total');

        // Bulan tanpa tahun: pilih tahun terbaru yang memiliki data.
        if ($month !== null && $year === null) {
            $year = (int) $archives->keys()->first();
        }

        $perPage = max(3, min(30, Setting::int('posts_per_page', (int) config('portal.pagination.posts'))));

        $posts = Post::query()
            ->published()
            ->whereNotNull('published_at')
            ->with(['category', 'author'])
            ->tap($applyFilters)
            ->when($year !== null, function (Builder $query) use ($year, $month): void {
                if ($month !== null) {
                    $query->whereYear('published_at', $year)->whereMonth('published_at', $month);

                    return;
                }

                $query->whereYear('published_at', $year);
            })
            ->latestPublished()
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::query()
            ->active()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $authors = User::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('name')
            ->get();

        $periodLabel = $this->periodLabel($year, $month);

        return view('archive.index', [
            'posts' => $posts,
            'archives' => $archives,
            'totalByYear' => $totalByYear,
            'totalAll' => $totalAll,
            'year' => $year,
            'month' => $month,
            'periodLabel' => $periodLabel,
            'category' => $category,
            'author' => $author,
            'categories' => $categories,
            'authors' => $authors,
        ]);
    }

    private function periodLabel(?int $year, ?int $month): ?string
    {
        if ($year === null) {
            return null;
        }

        if ($month === null) {
            return (string) $year;
        }

        return CarbonImmutable::create($year, $month, 1)->translatedFormat('F Y');
    }
}
