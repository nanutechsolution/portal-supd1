<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Bulletin;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ringkasan dan berkas laporan untuk satu bulan kalender.
 *
 * Laporan tidak pernah mengisi data yang tidak tersedia: kunjungan per bulan
 * hanya diambil dari rollup post_view_statistics, sedangkan posts.views tetap
 * dilaporkan sebagai total historis sejak berita dikelola.
 */
final class MonthlyReport
{
    public const MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public function __construct(public readonly CarbonImmutable $month) {}

    public static function fromString(string $value): self
    {
        $month = trim($value);

        if (preg_match(self::MONTH_PATTERN, $month) !== 1) {
            throw new InvalidArgumentException('Format bulan harus YYYY-MM, contoh: 2026-10.');
        }

        $parsed = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01');

        if ($parsed === false) {
            throw new InvalidArgumentException('Bulan yang diberikan tidak valid.');
        }

        return new self($parsed->startOfMonth());
    }

    public static function previousMonth(): self
    {
        return new self(CarbonImmutable::now()->subMonthNoOverflow()->startOfMonth());
    }

    public function periodKey(): string
    {
        return $this->month->format('Y-m');
    }

    public function periodLabel(): string
    {
        return $this->month->translatedFormat('F Y');
    }

    public function dateFrom(): string
    {
        return $this->month->toDateString();
    }

    public function dateUntil(): string
    {
        return $this->month->endOfMonth()->toDateString();
    }

    public function filename(): string
    {
        return 'laporan-bulanan-'.$this->periodKey().'.xlsx';
    }

    /**
     * @return array<string, mixed>
     */
    public function exportInput(): array
    {
        return [
            'jenis' => 'publikasi',
            'format' => 'xlsx',
            'status' => 'semua',
            'dateFrom' => $this->dateFrom(),
            'dateUntil' => $this->dateUntil(),
        ];
    }

    public function write(string $path): void
    {
        ReportExporter::write($this->exportInput(), $path);
    }

    /**
     * Angka ringkasan untuk isi email. Keterbatasan data kunjungan ikut
     * dilaporkan apa adanya, tanpa estimasi atau penambalan data lama.
     *
     * @return array{
     *     published: int,
     *     drafts: int,
     *     scheduled: int,
     *     bulletins: int,
     *     viewsInPeriod: int,
     *     viewsHistorical: int,
     *     viewsRecordedSince: ?string,
     *     topPosts: array<int, array{title: string, views: int, publishedAt: ?string}>
     * }
     */
    public function summary(): array
    {
        $from = $this->month->startOfDay();
        $to = $this->month->endOfMonth()->endOfDay();

        $published = (int) Post::query()
            ->where('status', PostStatus::Published)
            ->whereBetween('published_at', [$from, $to])
            ->count();

        $topPosts = Post::query()
            ->where('status', PostStatus::Published)
            ->whereBetween('published_at', [$from, $to])
            ->orderByDesc('views')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['title', 'views', 'published_at'])
            ->map(fn (Post $post): array => [
                'title' => $post->title,
                'views' => (int) $post->views,
                'publishedAt' => $post->published_at?->toIso8601String(),
            ])
            ->all();

        $recordedSince = DB::table('post_view_statistics')->min('view_date');

        return [
            'published' => $published,
            'drafts' => (int) Post::query()->where('status', PostStatus::Draft)->count(),
            'scheduled' => (int) Post::query()
                ->where('status', PostStatus::Published)
                ->where('published_at', '>', now())
                ->count(),
            'bulletins' => (int) Bulletin::query()
                ->where('is_published', true)
                ->whereBetween('published_at', [$from, $to])
                ->count(),
            'viewsInPeriod' => (int) DB::table('post_view_statistics')
                ->whereBetween('view_date', [$this->dateFrom(), $this->dateUntil()])
                ->sum('views'),
            'viewsHistorical' => (int) Post::query()->sum('views'),
            'viewsRecordedSince' => $recordedSince === null ? null : (string) $recordedSince,
            'topPosts' => $topPosts,
        ];
    }
}
