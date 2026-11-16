<?php

namespace App\Http\Middleware;

use App\Support\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan HTML halaman publik dari cache untuk pengunjung anonim.
 *
 * Hanya respons GET 200 text/html yang disimpan. Pengguna yang login selalu
 * mendapat render segar (tidak pernah menerima cache pengunjung lain).
 */
final class CachePublicPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PublicPageCache::enabled() || ! $request->isMethod('GET') || Auth::check()) {
            return $next($request);
        }

        $key = PublicPageCache::key($request->fullUrl());
        $cached = Cache::get($key);

        if (is_string($cached)) {
            return response($cached, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'public, max-age='.PublicPageCache::ttl(),
                'X-Page-Cache' => 'HIT',
            ]);
        }

        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type', '');

        if ($response->getStatusCode() === 200 && str_contains($contentType, 'text/html')) {
            Cache::put($key, $response->getContent(), PublicPageCache::ttl());
            $response->headers->set('X-Page-Cache', 'MISS');
        } else {
            $response->headers->set('X-Page-Cache', 'BYPASS');
        }

        return $response;
    }
}
