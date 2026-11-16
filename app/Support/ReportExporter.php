<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Builder dan penulis laporan (XLSX/CSV) untuk panel admin.
 *
 * Satu-satunya titik pembuatan file export; semua laporan melalui filter
 * yang sama (tanggal, kategori, status). Formula injection dinetralisir
 * pada setiap sel teks yang ditulis.
 */
final class ReportExporter
{
    public const TYPES = [
        'berita' => 'Daftar Berita',
        'publikasi' => 'Statistik Publikasi',
        'kunjungan' => 'Statistik Kunjungan',
        'terpopuler' => 'Berita Terpopuler',
        'kategori' => 'Distribusi per Kategori',
    ];

    public const STATUSES = [
        'semua' => 'Semua',
        'terbit' => 'Terbit',
        'terjadwal' => 'Terjadwal',
        'draf' => 'Draf',
    ];

    public const FORMATS = ['xlsx', 'csv'];

    /**
     * @param  array<string, mixed>  $input
     * @return array{jenis: string, format: string, status: string, dateFrom: ?string, dateUntil: ?string, categoryId: ?int}
     */
    public static function normalize(array $input): array
    {
        $jenis = is_string($input['jenis'] ?? null) && array_key_exists($input['jenis'], self::TYPES)
            ? $input['jenis']
            : 'berita';

        $format = (($input['format'] ?? 'xlsx') === 'csv') ? 'csv' : 'xlsx';

        $status = is_string($input['status'] ?? null) && array_key_exists($input['status'], self::STATUSES)
            ? $input['status']
            : 'semua';

        $dateFrom = self::parseDate($input['dateFrom'] ?? null);
        $dateUntil = self::parseDate($input['dateUntil'] ?? null);

        if ($dateFrom !== null && $dateUntil !== null && $dateFrom > $dateUntil) {
            [$dateFrom, $dateUntil] = [$dateUntil, $dateFrom];
        }

        $categoryId = null;

        if (filled($input['category_id'] ?? null) && (int) $input['category_id'] > 0) {
            $categoryId = (int) $input['category_id'];
        }

        return compact('jenis', 'format', 'status', 'dateFrom', 'dateUntil', 'categoryId');
    }

    public static function export(array $input): BinaryFileResponse
    {
        $normalized = self::normalize($input);

        $filename = self::filename($normalized);
        $path = rtrim((string) sys_get_temp_dir(), DIRECTORY_SEPARATOR).'/'.uniqid('laporan-', true).'-'.$filename;

        self::write($normalized, $path);

        return response()
            ->download($path, $filename, [
                'Content-Type' => $normalized['format'] === 'csv'
                    ? 'text/csv; charset=UTF-8'
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function write(array $input, string $path): void
    {
        $normalized = self::normalize($input);

        $writer = $normalized['format'] === 'csv' ? new CsvWriter : new XlsxWriter;
        $writer->openToFile($path);

        try {
            self::writeMeta($writer, $normalized);

            match ($normalized['jenis']) {
                'publikasi' => self::writePublicationReport($writer, $normalized),
                'kunjungan' => self::writeVisitsReport($writer, $normalized),
                'terpopuler' => self::writeTopPostsReport($writer, $normalized),
                'kategori' => self::writeCategoryReport($writer, $normalized),
                default => self::writePostListReport($writer, $normalized),
            };
        } finally {
            $writer->close();
        }
    }

    public static function filename(array $input): string
    {
        $normalized = self::normalize($input);

        return sprintf(
            'laporan-%s-%s.%s',
            $normalized['jenis'],
            now()->format('Ymd-His'),
            $normalized['format'],
        );
    }

    private static function parseDate(mixed $value): ?string
    {
        if (blank($value) || ! is_string($value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)?->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function writeMeta(mixed $writer, array $normalized): void
    {
        $typeLabel = self::TYPES[$normalized['jenis']];
        $statusLabel = self::STATUSES[$normalized['status']];
        $categoryLabel = $normalized['categoryId']
            ? (string) Category::query()->whereKey($normalized['categoryId'])->value('name') ?: 'Tidak diketahui'
            : 'Semua';
        $period = self::periodLabel($normalized['dateFrom'], $normalized['dateUntil']);

        self::addRow($writer, ['LAPORAN '.mb_strtoupper($typeLabel).' — PORTAL SUPD I']);
        self::addRow($writer, ['Periode', $period]);
        self::addRow($writer, ['Filter', 'Kategori: '.$categoryLabel.' | Status: '.$statusLabel]);
        self::addRow($writer, ['Dibuat', now()->translatedFormat('d F Y H:i').' WIB']);

        $minRollup = DB::table('post_view_statistics')->min('view_date');

        if ($normalized['jenis'] === 'kunjungan' || $normalized['jenis'] === 'publikasi') {
            if ($minRollup === null) {
                self::addRow($writer, ['Keterangan', 'Belum ada data rollup kunjungan (post_view_statistics) sejak fitur rollup diaktifkan. Kolom Total Views adalah total historis sejak awal, bukan data per hari/bulan.']);
            } else {
                self::addRow($writer, ['Keterangan', 'Data kunjungan per hari/bulan tersedia sejak '.CarbonImmutable::parse($minRollup)->translatedFormat('d F Y').'. Angka sebelum tanggal tersebut tidak tersedia; gunakan kolom Total Views (posts.views) sebagai total historis — bukan estimasi.']);
            }
        } elseif ($normalized['jenis'] === 'terpopuler' || $normalized['jenis'] === 'berita') {
            self::addRow($writer, ['Keterangan', 'Views adalah total historis (posts.views) sejak berita dikelola — bukan data per hari/bulan. Data kunjungan granular tersedia sejak rollup diaktifkan; lihat laporan Statistik Kunjungan.']);
        }

        self::addRow($writer, ['']);
    }

    private static function addRow(mixed $writer, array $cells): void
    {
        $writer->addRow(Row::fromValues(self::safeCells($cells)));
    }

    /**
     * @return array<int, mixed>
     */
    private static function safeCells(array $cells): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_int($value) || is_float($value) || $value === null) {
                return $value;
            }

            return CsvSanitizer::cell((string) $value);
        }, $cells);
    }

    private static function writePostListReport(mixed $writer, array $normalized): void
    {
        self::addRow($writer, ['Judul', 'Kategori', 'Penulis', 'Status', 'Tanggal Terbit', 'Views']);

        foreach (self::filteredPosts($normalized)->lazy(500) as $post) {
            self::addRow($writer, [
                $post->title,
                $post->category?->name,
                $post->author?->name,
                self::postStatusLabel($post),
                $post->published_at?->format('d M Y'),
                (int) $post->views,
            ]);
        }
    }

    private static function writeTopPostsReport(mixed $writer, array $normalized): void
    {
        self::addRow($writer, ['Judul', 'Kategori', 'Penulis', 'Status', 'Tanggal Terbit', 'Views']);

        $posts = self::filteredPosts($normalized)
            ->reorder()
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        foreach ($posts as $post) {
            self::addRow($writer, [
                $post->title,
                $post->category?->name,
                $post->author?->name,
                self::postStatusLabel($post),
                $post->published_at?->format('d M Y'),
                (int) $post->views,
            ]);
        }
    }

    private static function writePublicationReport(mixed $writer, array $normalized): void
    {
        $from = $normalized['dateFrom'] !== null ? CarbonImmutable::parse($normalized['dateFrom'])->startOfDay() : null;
        $until = $normalized['dateUntil'] !== null ? CarbonImmutable::parse($normalized['dateUntil'])->endOfDay() : null;

        $base = self::filteredPosts($normalized);

        $publishedInPeriod = (int) (clone $base)->count();
        $draftOverall = Post::query()->where('status', 'draft')->count();
        $scheduled = Post::query()->where('status', 'published')->where('published_at', '>', now())->count();
        $viewsTotal = (int) (clone $base)->sum('views');

        $visitsTotal = null;

        if ($from !== null && $until !== null) {
            $visitsTotal = (int) DB::table('post_view_statistics')
                ->whereBetween('view_date', [$from->toDateString(), $until->toDateString()])
                ->sum('views');
        }

        self::addRow($writer, ['RINGKASAN']);
        self::addRow($writer, ['Berita terbit (periode)', $publishedInPeriod]);
        self::addRow($writer, ['Draf (keseluruhan)', $draftOverall]);
        self::addRow($writer, ['Terjadwal (menunggu publikasi)', $scheduled]);
        self::addRow($writer, ['Total views (posts.views, historis)', $viewsTotal]);
        self::addRow($writer, [
            'Kunjungan rollup (periode)',
            $visitsTotal === null ? 'Tidak tersedia (periode tanpa batas tanggal)' : $visitsTotal,
        ]);
        self::addRow($writer, ['Rata-rata views per berita terbit', $publishedInPeriod > 0 ? round($viewsTotal / $publishedInPeriod, 1) : 0]);
        self::addRow($writer, ['']);

        self::addRow($writer, ['BULANAN / HARIAN']);
        self::addRow($writer, ['Periode', 'Terbit', 'Kunjungan']);

        foreach (self::bucketRows($from, $until, $normalized) as [$label, $terbit, $kunjungan]) {
            self::addRow($writer, [$label, $terbit, $kunjungan]);
        }
    }

    private static function writeVisitsReport(mixed $writer, array $normalized): void
    {
        $from = $normalized['dateFrom'] !== null ? CarbonImmutable::parse($normalized['dateFrom'])->startOfDay() : null;
        $until = $normalized['dateUntil'] !== null ? CarbonImmutable::parse($normalized['dateUntil'])->endOfDay() : null;

        $totalPostsViews = (int) Post::query()->sum('views');
        $rollupTotal = 0;

        if ($from !== null && $until !== null) {
            $rollupTotal = (int) DB::table('post_view_statistics')
                ->whereBetween('view_date', [$from->toDateString(), $until->toDateString()])
                ->sum('views');
        }

        self::addRow($writer, ['RINGKASAN']);
        self::addRow($writer, ['Total views historis (posts.views)', $totalPostsViews]);
        self::addRow($writer, ['Kunjungan rollup (periode)', $rollupTotal]);
        self::addRow($writer, ['']);

        self::addRow($writer, ['HARIAN / BULANAN']);
        self::addRow($writer, ['Tanggal', 'Kunjungan', 'Kumulatif']);

        $cumulative = 0;
        $rows = self::dailyVisitRows($from, $until, $normalized);

        foreach ($rows as [$label, $daily]) {
            $cumulative += $daily;
            self::addRow($writer, [$label, $daily, $cumulative]);
        }

        self::addRow($writer, ['TOTAL', $cumulative, '']);
    }

    private static function writeCategoryReport(mixed $writer, array $normalized): void
    {
        $from = $normalized['dateFrom'] !== null ? CarbonImmutable::parse($normalized['dateFrom'])->startOfDay() : null;
        $until = $normalized['dateUntil'] !== null ? CarbonImmutable::parse($normalized['dateUntil'])->endOfDay() : null;

        $base = self::filteredPosts($normalized);
        $publishedInPeriod = (clone $base)->count();
        $viewsInPeriod = (int) (clone $base)->sum('views');

        self::addRow($writer, ['RINGKASAN']);
        self::addRow($writer, ['Total berita terbit (periode)', $publishedInPeriod]);
        self::addRow($writer, ['Total views (periode)', $viewsInPeriod]);
        self::addRow($writer, ['']);

        self::addRow($writer, ['PER KATEGORI']);
        self::addRow($writer, ['Kategori', 'Terbit', 'Total Views', 'Share']);

        $categories = Category::query()
            ->where('is_active', true)
            ->when($normalized['categoryId'] !== null, fn ($query) => $query->whereKey($normalized['categoryId']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $countsByCategory = self::filteredPosts($normalized)
            ->reorder()
            ->selectRaw('category_id, COUNT(*) AS total, SUM(views) AS views')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $totalTerbit = 0;
        $totalViews = 0;

        foreach ($categories as $category) {
            $row = $countsByCategory->get($category->id);
            $terbit = $row ? (int) $row->total : 0;
            $views = $row ? (int) $row->views : 0;

            $totalTerbit += $terbit;
            $totalViews += $views;

            $share = $publishedInPeriod > 0 ? round($terbit / $publishedInPeriod * 100, 1).'%' : '0,0%';

            self::addRow($writer, [$category->name, $terbit, $views, $share]);
        }

        self::addRow($writer, ['TOTAL', $totalTerbit, $totalViews, $publishedInPeriod > 0 ? '100%' : '0,0%']);
    }

    /**
     * @return Builder<Post>
     */
    private static function filteredPosts(array $normalized): Builder
    {
        $from = $normalized['dateFrom'] !== null ? CarbonImmutable::parse($normalized['dateFrom'])->startOfDay() : null;
        $until = $normalized['dateUntil'] !== null ? CarbonImmutable::parse($normalized['dateUntil'])->endOfDay() : null;

        $query = Post::query()->with(['category', 'author']);

        if ($from !== null && $until !== null) {
            $query->whereBetween('published_at', [$from, $until]);
        }

        if ($normalized['categoryId'] !== null) {
            $query->where('category_id', $normalized['categoryId']);
        }

        match ($normalized['status']) {
            'terbit' => $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now()),
            'terjadwal' => $query->where('status', 'published')->where('published_at', '>', now()),
            'draf' => $query->where('status', 'draft'),
            default => null,
        };

        return $query->orderByRaw('COALESCE(published_at, created_at) DESC');
    }

    private static function periodLabel(?string $dateFrom, ?string $dateUntil): string
    {
        if ($dateFrom === null && $dateUntil === null) {
            return 'Semua waktu';
        }

        $from = $dateFrom !== null ? CarbonImmutable::parse($dateFrom)->translatedFormat('d F Y') : '…';
        $until = $dateUntil !== null ? CarbonImmutable::parse($dateUntil)->translatedFormat('d F Y') : '…';

        return $from.' s.d. '.$until;
    }

    /**
     * @return array<int, array{0: string, 1: int, 2: int}>
     */
    private static function bucketRows(?CarbonImmutable $from, ?CarbonImmutable $until, array $normalized): array
    {
        $postsQuery = self::filteredPosts($normalized)
            ->reorder()
            ->selectRaw('DATE(published_at) AS bucket, COUNT(*) AS total');

        if ($from !== null && $until !== null) {
            $postsQuery->whereBetween('published_at', [$from, $until]);
        }

        $postsByDate = $postsQuery->groupBy('bucket')->pluck('total', 'bucket');

        $visitsQuery = DB::table('post_view_statistics');

        if ($from !== null && $until !== null) {
            $visitsQuery->whereBetween('view_date', [$from->toDateString(), $until->toDateString()]);
        }

        $visitsByDate = $visitsQuery
            ->selectRaw('view_date AS bucket, SUM(views) AS total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        if ($from === null || $until === null) {
            // Seluruh waktu: kelompokkan per bulan.
            $buckets = [];
            foreach ($postsByDate as $bucket => $total) {
                $monthKey = substr((string) $bucket, 0, 7);
                $buckets[$monthKey] ??= ['terbit' => 0, 'kunjungan' => 0];
                $buckets[$monthKey]['terbit'] += (int) $total;
            }
            foreach ($visitsByDate as $bucket => $total) {
                $monthKey = substr((string) $bucket, 0, 7);
                $buckets[$monthKey] ??= ['terbit' => 0, 'kunjungan' => 0];
                $buckets[$monthKey]['kunjungan'] += (int) $total;
            }
            ksort($buckets);

            $rows = [];
            foreach ($buckets as $monthKey => ['terbit' => $terbit, 'kunjungan' => $kunjungan]) {
                $rows[] = [CarbonImmutable::parse($monthKey.'-01')->translatedFormat('F Y'), $terbit, $kunjungan];
            }

            return $rows;
        }

        $days = (int) $from->diffInDays($until) + 1;

        if ($days > 62) {
            $buckets = [];
            foreach ($postsByDate as $bucket => $total) {
                $monthKey = substr((string) $bucket, 0, 7);
                $buckets[$monthKey] ??= ['terbit' => 0, 'kunjungan' => 0];
                $buckets[$monthKey]['terbit'] += (int) $total;
            }
            foreach ($visitsByDate as $bucket => $total) {
                $monthKey = substr((string) $bucket, 0, 7);
                $buckets[$monthKey] ??= ['terbit' => 0, 'kunjungan' => 0];
                $buckets[$monthKey]['kunjungan'] += (int) $total;
            }
            ksort($buckets);

            $rows = [];
            foreach ($buckets as $monthKey => ['terbit' => $terbit, 'kunjungan' => $kunjungan]) {
                $rows[] = [CarbonImmutable::parse($monthKey.'-01')->translatedFormat('F Y'), $terbit, $kunjungan];
            }

            return $rows;
        }

        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($until)) {
            $key = $cursor->toDateString();
            $rows[] = [
                $cursor->translatedFormat('d M Y'),
                (int) ($postsByDate[$key] ?? 0),
                (int) ($visitsByDate[$key] ?? 0),
            ];
            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private static function dailyVisitRows(?CarbonImmutable $from, ?CarbonImmutable $until, array $normalized): array
    {
        if ($from === null || $until === null) {
            // Seluruh waktu: kelompokkan per bulan.
            $buckets = [];
            foreach (DB::table('post_view_statistics')
                ->selectRaw('view_date AS bucket, SUM(views) AS total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket') as $bucket => $total) {
                $monthKey = substr((string) $bucket, 0, 7);
                $buckets[$monthKey] = ($buckets[$monthKey] ?? 0) + (int) $total;
            }
            ksort($buckets);

            $rows = [];
            foreach ($buckets as $monthKey => $total) {
                $rows[] = [CarbonImmutable::parse($monthKey.'-01')->translatedFormat('F Y'), $total];
            }

            return $rows;
        }

        $visitsByDate = DB::table('post_view_statistics')
            ->whereBetween('view_date', [$from->toDateString(), $until->toDateString()])
            ->selectRaw('view_date AS bucket, SUM(views) AS total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $rows = [];
        $cursor = $from->copy();

        while ($cursor->lte($until)) {
            $key = $cursor->toDateString();
            $rows[] = [$cursor->translatedFormat('d M Y'), (int) ($visitsByDate[$key] ?? 0)];
            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    private static function postStatusLabel(Post $post): string
    {
        return match (true) {
            $post->status === PostStatus::Draft => 'Draf',
            $post->status === PostStatus::Published && $post->published_at !== null && $post->published_at->isFuture() => 'Terjadwal',
            default => 'Terbit',
        };
    }
}
