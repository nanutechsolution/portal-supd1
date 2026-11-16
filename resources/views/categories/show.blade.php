<x-layouts.public
    :title="$category->name"
    :description="$category->description ?: 'Kumpulan berita pada rubrik '.$category->name.'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-400">
                <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('posts.index') }}" class="hover:text-white">Berita</a>
                <span aria-hidden="true">/</span>
                <span class="text-ink-200">{{ $category->name }}</span>
            </nav>
            <p class="eyebrow mt-4 text-accent-300">Rubrik</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-3 max-w-3xl text-sm leading-relaxed text-ink-300">{{ $category->description }}</p>
            @endif
            <p class="mt-4 text-xs text-ink-400">
                {{ $posts->total() }} berita dalam rubrik ini
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('archive.index', ['kategori' => $category->slug]) }}" class="font-semibold text-accent-300 hover:text-white">
                    Arsip rubrik ini
                </a>
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-8">
            @if ($q !== '')
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-ink-200 bg-white px-4 py-3">
                    <p class="text-sm text-ink-700">
                        Hasil pencarian untuk <span class="font-semibold text-ink-950">&ldquo;{{ $q }}&rdquo;</span>
                        <span class="text-ink-500">({{ $posts->total() }} berita)</span>
                    </p>
                    <a href="{{ route('categories.show', $category) }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">Hapus pencarian</a>
                </div>
            @endif

            <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs uppercase tracking-[0.14em] text-ink-500">
                    {{ $posts->total() }} berita
                </p>
                <form action="{{ route('categories.show', $category) }}" method="GET" role="search" class="flex items-center gap-2">
                    @if ($q !== '')
                        <input type="hidden" name="q" value="{{ $q }}">
                    @endif
                    <label for="sort" class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Urutkan</label>
                    <select id="sort" name="sort"
                            onchange="this.form.submit()"
                            class="rounded border border-ink-200 bg-white px-3 py-2 text-sm text-ink-800 focus:border-accent-400 focus:outline-none">
                        @foreach (['terbaru' => 'Terbaru', 'terpopuler' => 'Terpopuler', 'terlama' => 'Terlama'] as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded border border-ink-200 bg-white px-3 py-2 text-sm text-ink-700 hover:border-ink-400 hover:text-ink-950">
                        Terapkan
                    </button>
                </form>
            </div>

            @if ($posts->isNotEmpty())
                <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <x-public.post-card :post="$post" />
                    @endforeach
                </div>

                {{ $posts->links('partials.pagination') }}
            @else
                @php
                    $isSearching = $q !== '';
                @endphp
                <x-public.empty-state
                    :title="$isSearching ? 'Berita tidak ditemukan' : 'Belum ada berita'"
                    :message="$isSearching ? 'Coba gunakan kata kunci lain pada rubrik ini.' : 'Rubrik ini belum memiliki berita yang diterbitkan.'"
                    :action="$isSearching ? route('categories.show', $category) : route('posts.index')"
                    :action-label="$isSearching ? 'Hapus pencarian' : 'Lihat semua berita'"
                />
            @endif
        </div>

        <aside class="space-y-8 lg:col-span-4">
            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-3 text-ink-950">Cari di Rubrik Ini</h2>
                <form action="{{ route('categories.show', $category) }}" method="GET" role="search" class="flex gap-2">
                    <label for="search-rubrik" class="sr-only">Kata kunci</label>
                    <input id="search-rubrik" type="search" name="q" value="{{ $q }}" maxlength="100"
                           placeholder="Kata kunci…"
                           class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm focus:border-accent-400 focus:outline-none">
                    @if ($sort !== 'terbaru')
                        <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    <button type="submit" class="rounded bg-ink-900 px-3 py-2 text-sm font-semibold text-white hover:bg-ink-800">
                        Cari
                    </button>
                </form>
            </div>

            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-4 text-ink-950">Rubrik Lain</h2>
                <ul class="space-y-1">
                    @foreach ($categories as $otherCategory)
                        <li>
                            <a href="{{ route('categories.show', $otherCategory) }}"
                               class="flex items-center justify-between gap-3 rounded px-2 py-2 text-sm transition
                                      {{ $otherCategory->is($category) ? 'bg-ink-50 font-semibold text-ink-950' : 'text-ink-700 hover:bg-ink-50 hover:text-ink-950' }}">
                                <span>{{ $otherCategory->name }}</span>
                                <span class="text-xs text-ink-400">{{ $otherCategory->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</x-layouts.public>
