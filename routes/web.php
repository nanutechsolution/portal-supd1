<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrganizationalStructureController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->middleware('cache.public')->name('home');

Route::get('/rss.xml', RssFeedController::class)->name('rss.feed');
Route::get('/feed', RssFeedController::class);

Route::get('/berita', [PostController::class, 'index'])
    ->middleware(['throttle:search', 'cache.public'])
    ->name('posts.index');
Route::get('/berita/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/berita/{post}/pratinjau', [PostController::class, 'preview'])->name('posts.preview');

Route::get('/arsip-berita', [ArchiveController::class, 'index'])->middleware('cache.public')->name('archive.index');

Route::get('/pencarian', [SearchController::class, 'index'])
    ->middleware('throttle:search')
    ->name('search.index');

Route::get('/kategori/{category}', [CategoryController::class, 'show'])
    ->middleware(['throttle:search', 'cache.public'])
    ->name('categories.show');

Route::get('/buletin', [BulletinController::class, 'index'])->middleware('cache.public')->name('bulletins.index');
Route::get('/buletin/{bulletin}', [BulletinController::class, 'show'])->name('bulletins.show');
Route::get('/buletin/{bulletin}/lihat', [BulletinController::class, 'preview'])
    ->middleware('throttle:downloads')
    ->name('bulletins.preview');
Route::get('/buletin/{bulletin}/unduh', [BulletinController::class, 'download'])
    ->middleware('throttle:downloads')
    ->name('bulletins.download');

Route::get('/galeri', [GalleryController::class, 'index'])->middleware('cache.public')->name('galleries.index');

Route::get('/profil', [PageController::class, 'profil'])->middleware('cache.public')->name('pages.profil');

Route::get('/kontak', [ContactController::class, 'index'])->name('contact.index');
Route::post('/kontak', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

Route::get('/struktur-organisasi', OrganizationalStructureController::class)->middleware('cache.public')->name('structure.index');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
