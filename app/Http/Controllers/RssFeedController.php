<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * RSS 2.0 feed berita yang sudah dipublikasikan.
 *
 * Item di-cache (default 10 menit) agar endpoint tidak membebani database,
 * dan di-invalidasi otomatis ketika post disimpan/dihapus.
 */
final class RssFeedController extends Controller
{
    /**
     * @var int
     */
    private const CACHE_MINUTES = 10;

    /**
     * @var int
     */
    private const ITEM_LIMIT = 20;

    public const CACHE_KEY = 'portal.rss.items';

    public function __invoke(): Response
    {
        $items = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), function (): array {
            return Post::query()
                ->published()
                ->with(['category', 'author'])
                ->latestPublished()
                ->limit(self::ITEM_LIMIT)
                ->get()
                ->map(fn (Post $post): array => $this->serializePost($post))
                ->all();
        });

        $siteName = Setting::get('site_short_name') ?: 'Portal Informasi';
        $root = rtrim((string) config('app.url'), '/');

        return response()->view('rss', [
            'items' => $items,
            'title' => $siteName.' — Berita',
            'description' => 'Berita, informasi kegiatan, dan publikasi '.(Setting::get('site_directorate') ?: $siteName).'.',
            'lastBuildDate' => now()->format(DATE_RSS),
            'homeUrl' => $root.'/',
            'selfUrl' => $root.'/rss.xml',
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * @return array{title: string, link: string, guid: string, pubDate: string, description: string, author: string, category: ?string, enclosure: ?array{url: string, type: string, length: int}}
     */
    private function serializePost(Post $post): array
    {
        $excerpt = $post->excerpt !== ''
            ? Str::limit($post->excerpt, 300)
            : Str::limit(strip_tags((string) $post->content), 300);

        $root = rtrim((string) config('app.url'), '/');
        $link = $root.route('posts.show', $post, false);

        $enclosure = null;

        if (is_string($post->featured_image) && $post->featured_image !== '') {
            try {
                $enclosure = [
                    'url' => $post->featuredImageUrl(),
                    'type' => $this->mimeTypeFor($post->featured_image),
                    'length' => (int) Storage::disk('public')->size($post->featured_image),
                ];
            } catch (\Throwable) {
                $enclosure = null;
            }
        }

        /** @var Carbon|null $publishedAt */
        $publishedAt = $post->published_at ?? $post->updated_at ?? now();

        return [
            'title' => $this->xmlSafe($post->title),
            'link' => $link,
            'guid' => $link,
            'pubDate' => $publishedAt->format(DATE_RSS),
            'description' => $this->xmlSafe($excerpt),
            'author' => $this->xmlSafe($post->author?->name ?? 'Redaksi '.(Setting::get('site_short_name') ?: 'Redaksi')),
            'category' => $post->category?->name ? $this->xmlSafe($post->category->name) : null,
            'enclosure' => $enclosure,
        ];
    }

    /**
     * URL absolut gambar + tautan di-generate dari APP_URL; di sini hanya
     * pembersihan karakter yang bisa merusak struktur XML.
     */
    private function xmlSafe(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim($value)) ?? '';
    }

    private function mimeTypeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
