<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Agregat statistik untuk dashboard /admin/analitik.
 *
 * Semua perhitungan dilakukan oleh database (COUNT/SUM/GROUP BY); tidak ada
 * seluruh tabel yang dimuat ke memori PHP untuk keperluan statistik.
 */
final class PortalAnalytics
{
    public const PERIOD_7 = '7';

    public const PERIOD_30 = '30';

    public const PERIOD_90 = '90';

    public const PERIOD_YEAR = 'year';

    public const CACHE_TTL_MINUTES = 5;

    /**
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return [
            self::PERIOD_7 => '7 hari terakhir',
            self::PERIOD_30 => '30 hari terakhir',
            self::PERIOD_90 => '90 hari terakhir',
            self::PERIOD_YEAR => 'Tahun berjalan',
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            self::PERIOD_7 => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            self::PERIOD_90 => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            self::PERIOD_YEAR => [$now->copy()->startOfYear()->startOfDay(), $now->copy()->endOfYear()->endOfDay()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    public static function publishedTotal(): int
    {
        return (int) Post::query()->published()->count();
    }

    public static function publishedInPeriod(string $period): int
    {
        [$from, $to] = self::bounds($period);

        return (int) Post::query()
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to])
            ->count();
    }

    public static function draftCount(): int
    {
        return (int) Post::query()->where('status', 'draft')->count();
    }

    public static function scheduledCount(): int
    {
        return (int) Post::query()
            ->where('status', 'published')
            ->where('published_at', '>', now())
            ->count();
    }

    public static function activeCategories(): int
    {
        return (int) Category::query()->where('is_active', true)->count();
    }

    /**
     * Total kunjungan dari seluruh berita (mekanisme yang sudah ada).
     */
    public static function viewsTotal(): int
    {
        return (int) Post::query()->sum('views');
    }

    /**
     * Kunjungan pada periode berdasarkan rollup harian post_view_statistics.
     */
    public static function viewsInPeriod(string $period): int
    {
        [$from, $to] = self::bounds($period);

        return (int) DB::table('post_view_statistics')
            ->whereBetween('view_date', [$from->toDateString(), $to->toDateString()])
            ->sum('views');
    }

    /**
     * Bucket label & data untuk grafik per periode.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function publicationBuckets(string $period): array
    {
        [$from, $to] = self::bounds($period);

        $rows = Post::query()
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to])
            ->selectRaw('DATE(published_at) AS bucket, COUNT(*) AS total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return self::bucketify($period, $from, $to, $rows);
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function viewsBuckets(string $period): array
    {
        [$from, $to] = self::bounds($period);

        $rows = DB::table('post_view_statistics')
            ->whereBetween('view_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('view_date AS bucket, SUM(views) AS total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return self::bucketify($period, $from, $to, $rows);
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function categoryDistribution(string $period): array
    {
        [$from, $to] = self::bounds($period);

        $rows = Post::query()
            ->where('status', 'published')
            ->whereBetween('published_at', [$from, $to])
            ->selectRaw('category_id, COUNT(*) AS total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $names = Category::query()
            ->whereIn('id', $rows->pluck('category_id'))
            ->pluck('name', 'id');

        return [
            'labels' => $rows->map(fn ($row): string => (string) ($names[$row->category_id] ?? 'Lainnya'))->all(),
            'data' => $rows->map(fn ($row): int => (int) $row->total)->all(),
        ];
    }

    /**
     * Catat satu kunjungan ke rollup harian dan bersihkan cache analitik.
     */
    public static function recordVisit(Post $post): void
    {
        DB::table('post_view_statistics')->upsert(
            [['post_id' => $post->getKey(), 'view_date' => now()->toDateString(), 'views' => 1]],
            ['post_id', 'view_date'],
            ['views' => DB::raw('post_view_statistics.views + 1')],
        );

        self::forgetCache();
    }

    /**
     * Snapshot agregat untuk satu periode, di-cache 5 menit.
     *
     * @return array{
     *     publishedInPeriod: int,
     *     viewsInPeriod: int,
     *     publication: array{labels: list<string>, data: list<int>},
     *     visits: array{labels: list<string>, data: list<int>},
     *     categories: array{labels: list<string>, data: list<int>}
     * }
     */
    public static function snapshot(string $period): array
    {
        return Cache::remember(
            self::cacheKey($period),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => [
                'publishedInPeriod' => self::publishedInPeriod($period),
                'viewsInPeriod' => self::viewsInPeriod($period),
                'publication' => self::publicationBuckets($period),
                'visits' => self::viewsBuckets($period),
                'categories' => self::categoryDistribution($period),
            ],
        );
    }

    public static function forgetCache(): void
    {
        foreach (self::periodOptions() as $period => $label) {
            Cache::forget(self::cacheKey((string) $period));
        }
    }

    /**
     * Normalisasi nilai filter periode (default: 30 hari).
     */
    public static function normalizePeriod(mixed $period): string
    {
        $period = is_scalar($period) ? (string) $period : self::PERIOD_30;

        return array_key_exists($period, self::periodOptions()) ? $period : self::PERIOD_30;
    }

    public static function cacheKey(string $period): string
    {
        return 'portal.analytics.'.$period;
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    private static function bucketify(string $period, Carbon $from, Carbon $to, Collection $bucketCounts): array
    {
        $counts = [];

        foreach ($bucketCounts as $bucket => $total) {
            $counts[(string) $bucket] = (int) $total;
        }

        if ($period === self::PERIOD_YEAR) {
            $labels = [];
            $data = [];
            $monthRows = [];

            foreach ($counts as $bucket => $total) {
                $month = (int) substr($bucket, 5, 2);
                $monthRows[$month] = ($monthRows[$month] ?? 0) + $total;
            }

            for ($month = 1; $month <= 12; $month++) {
                $labels[] = Carbon::createFromDate($from->year, $month, 1)->translatedFormat('M');
                $data[] = $monthRows[$month] ?? 0;
            }

            return ['labels' => $labels, 'data' => $data];
        }

        if ($period === self::PERIOD_90) {
            $labels = [];
            $data = [];
            $weekRows = [];

            foreach ($counts as $bucket => $total) {
                $weekStart = Carbon::parse($bucket)->startOfWeek(Carbon::MONDAY);
                $key = $weekStart->toDateString();
                $weekRows[$key] = ($weekRows[$key] ?? 0) + $total;
            }

            $cursor = $from->copy()->startOfWeek(Carbon::MONDAY);

            while ($cursor->lte($to)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->translatedFormat('d M');
                $data[] = $weekRows[$key] ?? 0;
                $cursor->addWeek();
            }

            return ['labels' => $labels, 'data' => $data];
        }

        $labels = [];
        $data = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->translatedFormat('d M');
            $data[] = $counts[$key] ?? 0;
            $cursor->addDay();
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
