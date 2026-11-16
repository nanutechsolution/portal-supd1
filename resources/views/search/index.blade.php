<x-layouts.public
    title="Pencarian"
    description="Cari berita, buletin, dan halaman pada portal SUPD I."
    :noindex="true"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-400">
                <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-ink-200">Pencarian</span>
            </nav>
            <p class="eyebrow mt-4 text-accent-300">Telusuri Portal</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Cari Berita, Buletin, dan Halaman</h1>

            <form action="{{ route('search.index') }}" method="GET" role="search" class="mt-6 flex flex-col gap-2 sm:flex-row">
                <label for="search-global" class="sr-only">Kata kunci pencarian</label>
                <input id="search-global" type="search" name="q" value="{{ $q }}" maxlength="100" autofocus
                       placeholder="Masukkan kata kunci, misalnya: sinkronisasi, buletin, struktur…"
                       class="w-full rounded-full border border-white/20 bg-white px-5 py-3 text-sm text-ink-900 placeholder:text-ink-400 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                <button type="submit"
                        class="rounded-full bg-accent-400 px-6 py-3 text-sm font-semibold text-ink-950 transition hover:bg-accent-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    Cari
                </button>
            </form>

            @if ($q !== '')
                <p class="mt-4 text-xs text-ink-300">
                    {{ $totalResults }} hasil untuk <span class="font-semibold text-white">&ldquo;{{ $q }}&rdquo;</span>
                    <span aria-hidden="true">&middot;</span>
                    <a href="{{ route('search.index') }}" class="font-semibold text-accent-300 hover:text-white">Hapus pencarian</a>
                </p>
            @else
                <p class="mt-4 text-xs text-ink-300">
                    Pencarian mencakup berita, buletin, dan halaman portal. Pencarian khusus berita juga tersedia di halaman Berita.
                </p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($q === '')
            <div class="rounded-lg border border-ink-100 bg-white p-6 sm:p-8">
                <h2 class="font-serif text-2xl font-bold text-ink-950">Mulai dengan kata kunci</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">
                    Gunakan nama kegiatan, instansi, topik, atau nomor edisi buletin. Untuk menelusuri berdasarkan waktu,
                    gunakan <a href="{{ route('archive.index') }}" class="font-semibold text-accent-700 hover:text-accent-800">Arsip Berita</a>.
                </p>

                <h3 class="eyebrow mt-8 text-ink-950">Saran Pencarian</h3>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($suggestions as $suggestion)
                        <li>
                            <a href="{{ $suggestion['url'] }}"
                               class="inline-flex rounded-full border border-ink-200 bg-paper px-3.5 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                                {{ $suggestion['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @elseif ($totalResults === 0)
            <x-public.empty-state
                title="Tidak ada hasil"
                :message="'Kata kunci “'.$q.'” tidak ditemukan pada berita, buletin, maupun halaman portal. Coba kata kunci yang lebih singkat atau gunakan saran di bawah.'"
                :action="route('posts.index', ['q' => $q])"
                action-label="Cari di berita saja"
            />

            <div class="mt-6 rounded-lg border border-ink-100 bg-white p-6">
                <h2 class="eyebrow text-ink-950">Saran Pencarian</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($suggestions as $suggestion)
                        <li>
                            <a href="{{ $suggestion['url'] }}"
                               class="inline-flex rounded-full border border-ink-200 bg-paper px-3.5 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                                {{ $suggestion['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            @if ($posts->isNotEmpty())
                <section aria-labelledby="hasil-berita" class="mb-12">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-ink-100 pb-3">
                        <div>
                            <h2 id="hasil-berita" class="font-serif text-2xl font-bold text-ink-950">Berita</h2>
                            <p class="mt-1 text-xs text-ink-500">{{ $postCount }} berita cocok</p>
                        </div>
                        @if ($postCount > $posts->count())
                            <a href="{{ route('posts.index', ['q' => $q]) }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">
                                Lihat semua hasil berita &rarr;
                            </a>
                        @endif
                    </div>

                    <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2">
                        @foreach ($posts as $post)
                            <x-public.post-card :post="$post" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($bulletins->isNotEmpty())
                <section aria-labelledby="hasil-buletin" class="mb-12">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-ink-100 pb-3">
                        <div>
                            <h2 id="hasil-buletin" class="font-serif text-2xl font-bold text-ink-950">Buletin</h2>
                            <p class="mt-1 text-xs text-ink-500">{{ $bulletinCount }} buletin cocok</p>
                        </div>
                        <a href="{{ route('bulletins.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">
                            Semua buletin &rarr;
                        </a>
                    </div>

                    <ul class="divide-y divide-ink-100 rounded-lg border border-ink-100 bg-white">
                        @foreach ($bulletins as $bulletin)
                            <li class="flex gap-4 p-4">
                                <a href="{{ route('bulletins.show', $bulletin) }}" class="shrink-0 overflow-hidden rounded" tabindex="-1" aria-hidden="true">
                                    <img src="{{ $bulletin->coverUrl() }}" alt="" loading="lazy" width="64" height="88"
                                         class="h-[88px] w-16 object-cover">
                                </a>
                                <div class="min-w-0">
                                    <p class="eyebrow text-accent-600">
                                        Edisi {{ $bulletin->edition_number }} &middot; {{ $bulletin->year }}
                                    </p>
                                    <h3 class="mt-1 font-serif text-base font-bold leading-snug text-ink-950">
                                        <a href="{{ route('bulletins.show', $bulletin) }}" class="hover:text-accent-600">{{ $bulletin->title }}</a>
                                    </h3>
                                    <p class="mt-1 text-xs text-ink-500">
                                        {{ $bulletin->published_at?->translatedFormat('j F Y') ?? 'Belum ada tanggal publikasi' }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($pages->isNotEmpty())
                <section aria-labelledby="hasil-halaman" class="mb-12">
                    <div class="mb-5 border-b border-ink-100 pb-3">
                        <h2 id="hasil-halaman" class="font-serif text-2xl font-bold text-ink-950">Halaman</h2>
                        <p class="mt-1 text-xs text-ink-500">{{ $pages->count() }} halaman cocok</p>
                    </div>

                    <ul class="divide-y divide-ink-100 rounded-lg border border-ink-100 bg-white">
                        @foreach ($pages as $page)
                            <li class="p-4">
                                <p class="eyebrow text-ink-400">{{ $page['label'] }}</p>
                                <h3 class="mt-1 font-serif text-base font-bold text-ink-950">
                                    <a href="{{ $page['url'] }}" class="hover:text-accent-600">{{ $page['title'] }}</a>
                                </h3>
                                @if ($page['description'] !== '')
                                    <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink-600">{{ $page['description'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="rounded-lg border border-ink-100 bg-paper p-5">
                <h2 class="eyebrow text-ink-950">Telusuri Lebih Lanjut</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li>
                        <a href="{{ route('archive.index') }}" class="inline-flex rounded-full border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            Arsip per tahun &amp; bulan
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('categories.show', $category) }}" class="inline-flex rounded-full border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                                Rubrik {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layouts.public>
