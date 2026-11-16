<?php

namespace App\Providers;

use App\Http\Controllers\RssFeedController;
use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\AuditAuthentication;
use App\Support\ModelAuditObserver;
use App\Support\PortalAnalytics;
use App\Support\PublicPageCache;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('downloads', function (Request $request): Limit {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('search', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('contact', function (Request $request): Limit {
            return Limit::perHour(5)->by($request->ip());
        });

        foreach ([
            Post::class,
            Bulletin::class,
            GalleryItem::class,
            Category::class,
            OrganizationalUnit::class,
            Page::class,
            User::class,
        ] as $model) {
            $model::observe(ModelAuditObserver::class);
        }

        // Cache HTML halaman publik dibuang setiap data yang tampil di
        // halaman tersebut berubah. Model::increment() (views/downloads)
        // hanya memicu event "updated", bukan "saved", sehingga penghitung
        // kunjungan tidak pernah mengganggu cache.
        foreach ([Category::class, GalleryItem::class, Page::class, OrganizationalUnit::class, User::class] as $model) {
            $model::created(fn () => PublicPageCache::flush());
            $model::updated(fn () => PublicPageCache::flush());
            $model::deleted(fn () => PublicPageCache::flush());
        }

        Bulletin::saved(fn () => PublicPageCache::flush());
        Bulletin::deleted(fn () => PublicPageCache::flush());

        Event::listen(Login::class, [AuditAuthentication::class, 'login']);
        Event::listen(Failed::class, [AuditAuthentication::class, 'failed']);
        Event::listen(Logout::class, [AuditAuthentication::class, 'logout']);

        Post::saved(function (): void {
            Cache::forget(RssFeedController::CACHE_KEY);
            PortalAnalytics::forgetCache();
            PublicPageCache::flush();
        });
        Post::deleted(function (): void {
            Cache::forget(RssFeedController::CACHE_KEY);
            PortalAnalytics::forgetCache();
            PublicPageCache::flush();
        });

        View::composer('*', function ($view): void {
            $view->with('site', Setting::values());
        });

        View::composer('components.layouts.public', function ($view): void {
            $view->with([
                'navCategories' => Category::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
            ]);
        });
    }
}
