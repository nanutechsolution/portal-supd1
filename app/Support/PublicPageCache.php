<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Cache HTML halaman publik yang tidak bergantung pada sesi pengguna.
 *
 * Kunci cache memuat:
 * - versi konten yang dinaikkan setiap kali data publik berubah;
 * - batas publikasi berikutnya, sehingga berita terjadwal langsung muncul
 *   pada menit yang sama tanpa menunggu TTL dan tanpa pernah tampil lebih awal;
 * - URL lengkap (termasuk query string).
 *
 * Halaman detail berita tidak di-cache karena setiap kunjungan menambah views.
 */
final class PublicPageCache
{
    private const VERSION_KEY = 'portal.public-page.version';

    public static function enabled(): bool
    {
        return (bool) config('portal.public_cache.enabled', true);
    }

    public static function ttl(): int
    {
        return max(30, min(3600, (int) config('portal.public_cache.ttl', 300)));
    }

    public static function key(string $url): string
    {
        return 'portal.page.'.sha1(implode('|', [
            (string) self::version(),
            self::publicationBoundary(),
            $url,
        ]));
    }

    /**
     * Invalidasi seluruh halaman publik yang tersimpan.
     */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * Timestamp publikasi terjadwal terdekat, atau "none".
     *
     * Begitu waktu tersebut terlewati, kunci cache berubah sehingga render
     * berikutnya memuat berita yang baru aktif. Sebelum waktunya, berita
     * tersebut tetap tidak ada di HTML yang di-cache.
     */
    private static function publicationBoundary(): string
    {
        $next = Post::query()
            ->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->min('published_at');

        return $next === null ? 'none' : (string) CarbonImmutable::parse($next)->getTimestamp();
    }
}
