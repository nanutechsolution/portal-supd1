@php
    $isHome = request()->routeIs('home');
    $headerLogo = \App\Models\Setting::mediaUrl('site_logo');
    $showTopbar = \App\Models\Setting::bool('show_header_topbar', true);
    $showSearch = \App\Models\Setting::bool('show_header_search', true);

    $siteName = trim((string) ($site['site_name'] ?? '')) ?: 'Portal Informasi';
    $siteShortName = trim((string) ($site['site_short_name'] ?? '')) ?: $siteName;
    $sitePortalLabel = trim((string) ($site['site_portal_label'] ?? ''));
    $siteDirectorate = trim((string) ($site['site_directorate'] ?? ''));
    $ministryLine1 = trim((string) ($site['site_ministry_line1'] ?? 'Direktorat Jenderal Bina Pembangunan Daerah'));
    $ministryLine2 = trim((string) ($site['site_ministry_line2'] ?? 'Kementerian Dalam Negeri'));
@endphp

@if ($showTopbar)
    <div class="hidden border-b border-ink-100 bg-paper-dark/70 lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-2 text-xs text-ink-600 sm:px-6 lg:px-8">
            <p class="eyebrow text-ink-600">{{ now()->translatedFormat('l, j F Y') }}</p>
            <nav aria-label="Navigasi sekunder" class="flex items-center gap-6">
                <a href="{{ route('pages.profil') }}" class="font-medium hover:text-accent-700">Profil Direktorat</a>
                <a href="{{ route('posts.index') }}" class="font-medium hover:text-accent-700">Berita</a>
                <a href="{{ route('bulletins.index') }}" class="font-medium hover:text-accent-700">Buletin</a>
                <a href="{{ route('contact.index') }}" class="font-medium hover:text-accent-700">Kontak</a>
            </nav>
        </div>
    </div>
@endif

<header class="relative border-b border-ink-100 bg-paper">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:gap-6 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
        <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4 lg:gap-5"
           aria-label="{{ $siteName }} — Beranda">
            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 ring-1 ring-ink-100 sm:h-16 sm:w-16 lg:h-28 lg:w-28">
                @if ($headerLogo)
                    <img src="{{ $headerLogo }}" alt="Logo {{ $siteShortName }}"
                         class="h-full w-full object-contain">
                @else
                    <x-public.logo />
                @endif
            </span>

            <span class="hidden h-14 w-px shrink-0 bg-ink-200 sm:block lg:h-24" aria-hidden="true"></span>

            {{-- Identitas ringkas: mobile dan tablet --}}
            <span class="min-w-0 leading-tight lg:hidden">
                <span class="block break-words font-serif text-xl font-bold tracking-tight text-ink-950 sm:text-2xl">
                    {{ $siteShortName }}
                </span>
                @if ($sitePortalLabel !== '')
                    <span class="eyebrow mt-1 block break-words text-[0.625rem] tracking-[0.1em] text-accent-600 sm:text-[0.6875rem] sm:tracking-[0.14em]">
                        {{ $sitePortalLabel }}
                    </span>
                @endif
            </span>

            {{-- Identitas lengkap: desktop --}}
            <span class="hidden min-w-0 leading-tight lg:block">
                <span class="block font-serif text-3xl font-bold tracking-tight text-ink-950">
                    {{ $siteShortName }}
                </span>

                @if ($sitePortalLabel !== '')
                    <span class="eyebrow mt-1 block text-accent-600">
                        {{ $sitePortalLabel }}
                    </span>
                @endif

                @if ($siteDirectorate !== '')
                    <span class="mt-1 block break-words text-sm font-bold leading-tight text-ink-600">
                        {{ $siteDirectorate }}
                    </span>
                @endif

                @if ($ministryLine1 !== '')
                    <span class="mt-1 block break-words text-sm font-bold leading-tight text-ink-800">
                        {{ $ministryLine1 }}
                    </span>
                @endif

                @if ($ministryLine2 !== '')
                    <span class="mt-0.5 block break-words text-sm font-bold leading-tight text-ink-800">
                        {{ $ministryLine2 }}
                    </span>
                @endif
            </span>
        </a>

        <div class="flex shrink-0 items-center gap-2">
            @if ($showSearch)
                <form action="{{ route('search.index') }}" method="GET" role="search"
                      class="hidden max-w-sm items-center gap-2 lg:flex">
                    <label for="search-desktop" class="sr-only">Cari berita, buletin, atau halaman</label>
                    <input id="search-desktop" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                           placeholder="Cari berita, buletin, atau halaman…"
                           class="h-11 w-full rounded-full border border-ink-200 bg-white px-4 text-sm text-ink-800 placeholder:text-ink-400 focus:border-accent-400 focus:outline-none lg:w-72">
                    <button type="submit"
                            class="h-11 shrink-0 rounded-full bg-ink-900 px-4 text-sm font-semibold text-white transition hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400">
                        Cari
                    </button>
                </form>
            @endif

            <button type="button" data-mobile-toggle aria-expanded="false" aria-controls="mobile-menu"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-ink-200 bg-white text-ink-700 transition hover:border-ink-300 hover:text-ink-950 lg:hidden">
                <span class="sr-only">Buka menu</span>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu" data-mobile-menu class="hidden border-t border-ink-100 bg-paper px-4 py-4 lg:hidden">
        @if ($showSearch)
            <form action="{{ route('search.index') }}" method="GET" role="search" class="mb-4 flex items-center gap-2">
                <label for="search-mobile" class="sr-only">Cari berita, buletin, atau halaman</label>
                <input id="search-mobile" type="search" name="q" value="{{ request('q') }}" maxlength="100"
                       placeholder="Cari berita, buletin, halaman…"
                       class="h-11 w-full rounded-lg border border-ink-200 bg-white px-3 text-sm focus:border-accent-400 focus:outline-none">
                <button type="submit" class="h-11 shrink-0 rounded-lg bg-ink-900 px-4 text-sm font-semibold text-white">Cari</button>
            </form>
        @endif

        <nav aria-label="Navigasi utama seluler" class="grid gap-1">
            <a href="{{ route('home') }}" @if ($isHome) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ $isHome ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Beranda</a>
            <a href="{{ route('posts.index') }}" @if (request()->routeIs('posts.*')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('posts.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Berita</a>
            <a href="{{ route('bulletins.index') }}" @if (request()->routeIs('bulletins.*')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('bulletins.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Buletin</a>
            <a href="{{ route('galleries.index') }}" @if (request()->routeIs('galleries.*')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('galleries.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Galeri</a>
            <a href="{{ route('structure.index') }}" @if (request()->routeIs('structure.*')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('structure.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Struktur</a>
            <a href="{{ route('pages.profil') }}" @if (request()->routeIs('pages.profil')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('pages.profil') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Profil</a>
            <a href="{{ route('contact.index') }}" @if (request()->routeIs('contact.*')) aria-current="page" @endif
               class="flex min-h-11 items-center rounded px-3 text-sm font-semibold {{ request()->routeIs('contact.*') ? 'bg-ink-50 text-ink-950' : 'text-ink-700' }}">Kontak</a>
        </nav>

        <p class="eyebrow mt-4 mb-2 text-ink-600">Rubrik</p>
        <nav aria-label="Kategori seluler" class="grid gap-1">
            @foreach ($navCategories as $category)
                <a href="{{ route('categories.show', $category) }}" title="{{ $category->name }}"
                   class="flex min-h-11 items-center rounded px-3 text-sm text-ink-700 hover:bg-ink-50 hover:text-ink-950">
                    {{ $category->name }}
                </a>
            @endforeach
        </nav>
    </div>
</header>

<div class="sticky top-0 z-40 border-b border-ink-100 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" @if ($isHome) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-bold uppercase tracking-wide transition sm:px-3
                  {{ $isHome ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Beranda
        </a>
        <a href="{{ route('posts.index') }}" @if (request()->routeIs('posts.*')) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('posts.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Berita
        </a>
        <a href="{{ route('bulletins.index') }}" @if (request()->routeIs('bulletins.*')) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('bulletins.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Buletin
        </a>
        <a href="{{ route('galleries.index') }}" @if (request()->routeIs('galleries.*')) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('galleries.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Galeri
        </a>
        <a href="{{ route('structure.index') }}" @if (request()->routeIs('structure.*')) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('structure.*') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Struktur
        </a>
        <a href="{{ route('pages.profil') }}" @if (request()->routeIs('pages.profil')) aria-current="page" @endif
           class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-2 py-3 text-sm font-semibold uppercase tracking-wide transition sm:px-3
                  {{ request()->routeIs('pages.profil') ? 'border-accent-400 text-ink-950' : 'border-transparent text-ink-600 hover:text-ink-950' }}">
            Profil
        </a>
    </div>

    @if ($navCategories->isNotEmpty())
        <div class="bg-ink-900">
            <div class="mx-auto flex max-w-7xl items-center gap-1 px-4 py-0.5 sm:gap-2 sm:px-6 lg:px-8 lg:py-1.5">
                <span class="eyebrow shrink-0 pr-1 text-accent-300 sm:pr-2">Rubrik</span>

                <div class="relative min-w-0 flex-1">
                    <div data-rubrik-fade="left"
                         class="pointer-events-none absolute inset-y-0 left-0 z-10 w-10 bg-linear-to-r from-ink-900 via-ink-900/80 to-transparent opacity-0 transition-opacity duration-200 sm:w-16"></div>
                    <div data-rubrik-fade="right"
                         class="pointer-events-none absolute inset-y-0 right-0 z-10 w-10 bg-linear-to-l from-ink-900 via-ink-900/80 to-transparent transition-opacity duration-200 sm:w-16"></div>

                    <div data-rubrik-scroll
                         class="no-scrollbar flex items-center gap-1 overflow-x-auto overscroll-x-contain py-0.5 motion-safe:scroll-smooth lg:py-1.5">
                        @foreach ($navCategories as $category)
                            <a href="{{ route('categories.show', $category) }}" title="{{ $category->name }}"
                               class="inline-flex min-h-11 shrink-0 items-center rounded px-3 text-xs font-medium text-ink-100 transition hover:bg-white/10 hover:text-white lg:min-h-0
                                      {{ request()->routeIs('categories.show') && request()->route('category')?->is($category) ? 'bg-white/10 text-white' : '' }}">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>

                    <button type="button" data-rubrik-prev aria-label="Geser rubrik ke kiri" aria-hidden="true" tabindex="-1"
                            class="pointer-events-none absolute left-0 top-1/2 z-20 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-ink-800/95 text-ink-100 opacity-0 shadow transition hover:bg-ink-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 sm:h-8 sm:w-8 lg:h-7 lg:w-7">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button type="button" data-rubrik-next aria-label="Geser rubrik ke kanan" aria-hidden="true" tabindex="-1"
                            class="pointer-events-none absolute right-0 top-1/2 z-20 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-ink-800/95 text-ink-100 opacity-0 shadow transition hover:bg-ink-700 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 sm:h-8 sm:w-8 lg:h-7 lg:w-7">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
