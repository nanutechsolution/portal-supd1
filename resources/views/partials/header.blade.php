@php
    $isHome = request()->routeIs('home');
    $headerLogo = \App\Models\Setting::mediaUrl('site_logo');
    $showTopbar = \App\Models\Setting::bool('show_header_topbar', true);
    $showSearch = \App\Models\Setting::bool('show_header_search', true);
@endphp

@if ($showTopbar)
    <div class="hidden border-b border-ink-100 bg-paper-dark/70 lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-2 text-xs text-ink-600 sm:px-6 lg:px-8">
            <p class="eyebrow text-ink-500">{{ now()->translatedFormat('l, j F Y') }}</p>
            <nav aria-label="Navigasi sekunder" class="flex items-center gap-6">
                <a href="{{ route('pages.profil') }}" class="hover:text-accent-600">Profil Direktorat</a>
                <a href="{{ route('posts.index') }}" class="hover:text-accent-600">Berita</a>
                <a href="{{ route('bulletins.index') }}" class="hover:text-accent-600">Buletin</a>
                <a href="{{ route('contact.index') }}" class="hover:text-accent-600">Kontak</a>
            </nav>
        </div>
    </div>
@endif

<header class="border-b border-ink-100 bg-paper">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-5 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $site['site_name'] }} — Beranda">
            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center sm:h-12 sm:w-12">
                @if ($headerLogo)
                    <img src="{{ $headerLogo }}" alt="Logo {{ $site['site_short_name'] }}"
                         class="h-full w-full object-contain">
                @else
                    <x-public.logo />
                @endif
            </span>
            <span class="leading-tight">
                <span class="block font-serif text-lg font-bold tracking-tight text-ink-950 sm:text-xl">
                    {{ $site['site_short_name'] }}
                </span>
                <span class="mt-0.5 block text-[11px] font-medium text-ink-600 sm:text-xs">
                    {{ $site['site_portal_label'] ?: 'Portal Informasi' }}
                </span>
                <span class="mt-0.5 hidden max-w-md text-[10px] uppercase tracking-[0.1em] text-ink-400 sm:block">
                    {{ $site['site_directorate'] }}
                </span>
            </span>
        </a>

        @if ($showSearch)
            <form action="{{ route('search.index') }}" method="GET" role="search" class="hidden w-full max-w-sm items-center gap-2 md:flex">
                <label for="search-desktop" class="sr-only">Cari berita, buletin, atau halaman</label>
                <input id="search-desktop" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                       placeholder="Cari berita, buletin, atau halaman…"
                       class="w-full rounded-full border border-ink-200 bg-white px-4 py-2 text-sm text-ink-800 placeholder:text-ink-400 focus:border-accent-400 focus:outline-none">
                <button type="submit"
                        class="rounded-full bg-ink-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400">
                    Cari
                </button>
            </form>
        @endif

        <button type="button" data-mobile-toggle aria-expanded="false" aria-controls="mobile-menu"
                class="inline-flex items-center justify-center rounded border border-ink-200 p-2 text-ink-700 md:hidden">
            <span class="sr-only">Buka menu</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>

    <div id="mobile-menu" data-mobile-menu class="hidden border-t border-ink-100 bg-paper px-4 py-4 md:hidden">
        @if ($showSearch)
            <form action="{{ route('search.index') }}" method="GET" role="search" class="mb-4 flex gap-2">
                <label for="search-mobile" class="sr-only">Cari berita, buletin, atau halaman</label>
                <input id="search-mobile" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                       placeholder="Cari berita, buletin, halaman…"
                       class="w-full rounded border border-ink-200 bg-white px-3 py-2 text-sm focus:border-accent-400 focus:outline-none">
                <button type="submit" class="rounded bg-ink-900 px-3 py-2 text-sm font-semibold text-white">Cari</button>
            </form>
        @endif

        <nav aria-label="Navigasi utama seluler" class="grid gap-1">
            <a href="{{ route('home') }}" class="rounded px-2 py-2 text-sm font-semibold {{ $isHome ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Beranda</a>
            <a href="{{ route('posts.index') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('posts.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Berita</a>
            <a href="{{ route('bulletins.index') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('bulletins.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Buletin</a>
            <a href="{{ route('galleries.index') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('galleries.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Galeri</a>
            <a href="{{ route('structure.index') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('structure.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Struktur</a>
            <a href="{{ route('pages.profil') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('pages.profil') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Profil</a>
            <a href="{{ route('contact.index') }}" class="rounded px-2 py-2 text-sm font-semibold {{ request()->routeIs('contact.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Kontak</a>
        </nav>

        <p class="eyebrow mt-4 mb-2 text-ink-400">Rubrik</p>
        <nav aria-label="Kategori seluler" class="grid gap-1">
            @foreach ($navCategories as $category)
                <a href="{{ route('categories.show', $category) }}" title="{{ $category->name }}" class="rounded px-2 py-1.5 text-sm text-ink-600 hover:bg-ink-50 hover:text-ink-950">
                    {{ $category->name }}
                </a>
            @endforeach
        </nav>
    </div>
</header>

<div class="sticky top-0 z-40 border-b border-ink-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-bold uppercase tracking-wide transition sm:px-3
                  {{ $isHome ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Beranda
        </a>
        <a href="{{ route('posts.index') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('posts.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Berita
        </a>
        <a href="{{ route('bulletins.index') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('bulletins.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Buletin
        </a>
        <a href="{{ route('galleries.index') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('galleries.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Galeri
        </a>
        <a href="{{ route('structure.index') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('structure.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Struktur
        </a>
        <a href="{{ route('pages.profil') }}"
           class="shrink-0 border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('pages.profil') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Profil
        </a>
    </div>

    @if ($navCategories->isNotEmpty())
        <div class="bg-ink-900">
            <div class="mx-auto flex max-w-7xl items-center gap-1 px-4 py-1.5 sm:gap-2 sm:px-6 lg:px-8">
                <span class="eyebrow shrink-0 pr-1 text-accent-300 sm:pr-2">Rubrik</span>

                <div class="relative min-w-0 flex-1">
                    <div data-rubrik-fade="left"
                         class="pointer-events-none absolute inset-y-0 left-0 z-10 w-10 bg-linear-to-r from-ink-900 via-ink-900/80 to-transparent opacity-0 transition-opacity duration-200 sm:w-16"></div>
                    <div data-rubrik-fade="right"
                         class="pointer-events-none absolute inset-y-0 right-0 z-10 w-10 bg-linear-to-l from-ink-900 via-ink-900/80 to-transparent transition-opacity duration-200 sm:w-16"></div>

                    <div data-rubrik-scroll
                         class="no-scrollbar flex items-center gap-1 overflow-x-auto overscroll-x-contain py-1.5 motion-safe:scroll-smooth">
                        @foreach ($navCategories as $category)
                            <a href="{{ route('categories.show', $category) }}" title="{{ $category->name }}"
                               class="shrink-0 rounded px-3 py-1.5 text-xs font-medium text-ink-100 transition hover:bg-white/10 hover:text-white
                                      {{ request()->routeIs('categories.show') && request()->route('category')?->is($category) ? 'bg-white/10 text-white' : '' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>

                    <button type="button" data-rubrik-prev aria-label="Geser rubrik ke kiri" aria-hidden="true" tabindex="-1"
                            class="pointer-events-none absolute left-0 top-1/2 z-20 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-ink-800/95 text-ink-100 opacity-0 shadow transition hover:bg-ink-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button type="button" data-rubrik-next aria-label="Geser rubrik ke kanan" aria-hidden="true" tabindex="-1"
                            class="pointer-events-none absolute right-0 top-1/2 z-20 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-ink-800/95 text-ink-100 opacity-0 shadow transition hover:bg-ink-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
