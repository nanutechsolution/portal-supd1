<x-layouts.public
    title="Berita"
    :description="'Berita, informasi kegiatan, dan publikasi '.$site['site_directorate'].'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow text-accent-300">Publikasi</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Berita &amp; Informasi</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">
                Kumpulan berita dan informasi kegiatan {{ $site['site_short_name'] }} beserta unit kerja di bawahnya.
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-8">
            <a href="{{ route('archive.index') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-ink-700 hover:text-accent-600">
                Arsip berita per tahun dan bulan <span aria-hidden="true">&rarr;</span>
            </a>
            @if ($q !== '')
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 bg-white px-4 py-3">
                    <p class="text-sm text-ink-700">
                        Hasil pencarian untuk <span class="font-semibold text-ink-950">&ldquo;{{ $q }}&rdquo;</span>
                        <span class="text-ink-500">({{ $posts->total() }} berita)</span>
                    </p>
                    <a href="{{ route('posts.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">Hapus pencarian</a>
                </div>
            @endif

            <nav aria-label="Filter kategori" class="mb-8 flex flex-wrap gap-2">
                <a href="{{ route('posts.index', array_filter(['q' => $q])) }}"
                   class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition
                          {{ $categorySlug ? 'border-ink-200 bg-white text-ink-600 hover:border-ink-400' : 'border-ink-900 bg-ink-900 text-white' }}">
                    Semua
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('posts.index', array_filter(['q' => $q, 'kategori' => $category->slug])) }}"
                       class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition
                              {{ $categorySlug === $category->slug ? 'border-ink-900 bg-ink-900 text-white' : 'border-ink-200 bg-white text-ink-600 hover:border-ink-400' }}">
                        {{ $category->name }} <span class="{{ $categorySlug === $category->slug ? 'text-white/60' : 'text-ink-400' }}">{{ $category->posts_count }}</span>
                    </a>
                @endforeach
            </nav>

            @if ($posts->isNotEmpty())
                <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <x-public.post-card :post="$post" />
                    @endforeach
                </div>

                {{ $posts->links('partials.pagination') }}
            @else
                <x-public.empty-state
                    title="Berita tidak ditemukan"
                    message="{{ $q !== '' ? 'Coba gunakan kata kunci lain atau pilih kategori berbeda.' : 'Belum ada berita yang diterbitkan pada bagian ini.' }}"
                    :action="route('posts.index')"
                    action-label="Lihat semua berita"
                />
            @endif
        </div>

        <aside class="space-y-8 lg:col-span-4">
            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-3 text-ink-950">Cari Berita</h2>
                <form action="{{ route('posts.index') }}" method="GET" role="search" class="flex gap-2">
                    <label for="search-sidebar" class="sr-only">Kata kunci</label>
                    <input id="search-sidebar" type="search" name="q" value="{{ $q }}" maxlength="100"
                           placeholder="Kata kunci…"
                           class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm focus:border-accent-400 focus:outline-none">
                    <button type="submit" class="rounded bg-ink-900 px-3 py-2 text-sm font-semibold text-white hover:bg-ink-800">
                        Cari
                    </button>
                </form>
            </div>

            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-4 text-ink-950">Rubrik</h2>
                <ul class="space-y-1">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('categories.show', $category) }}"
                               class="flex items-center justify-between gap-3 rounded px-2 py-2 text-sm text-ink-700 hover:bg-ink-50 hover:text-ink-950">
                                <span>{{ $category->name }}</span>
                                <span class="text-xs text-ink-400">{{ $category->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-4 text-ink-950">Paling Dibaca</h2>
                <ol class="space-y-4">
                    @forelse ($popular as $index => $popularPost)
                        <li class="flex gap-3">
                            <span class="font-serif text-xl font-bold leading-none text-ink-200">
                                {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('posts.show', $popularPost) }}"
                                   class="font-serif text-sm font-bold leading-snug text-ink-900 hover:text-accent-600">
                                    {{ $popularPost->title }}
                                </a>
                                <p class="mt-1 text-[11px] text-ink-500">{{ $popularPost->views }} kali dibaca</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">Belum ada data kunjungan.</li>
                    @endforelse
                </ol>
            </div>
        </aside>
    </div>
</x-layouts.public>
