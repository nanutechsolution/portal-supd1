<x-layouts.public>
    @if ($ticker->isNotEmpty() && \App\Models\Setting::bool('breaking_news_enabled', true))
        <div class="ticker overflow-hidden border-b border-ink-100 bg-white">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <span class="eyebrow shrink-0 bg-accent-400 px-2 py-1 text-ink-950">Terkini</span>
                <div class="min-w-0 flex-1 overflow-hidden">
                    <div class="ticker-track flex w-max items-center gap-10 py-2">
                        @for ($repeat = 0; $repeat < 2; $repeat++)
                            @foreach ($ticker as $tickerPost)
                                <a href="{{ route('posts.show', $tickerPost) }}"
                                   class="inline-flex shrink-0 items-center gap-3 text-xs text-ink-700 hover:text-accent-600">
                                    @if ($tickerPost->is_breaking)
                                        <span class="rounded-sm bg-red-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Breaking</span>
                                    @else
                                        <span class="h-1 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                                    @endif
                                    <span class="whitespace-nowrap">{{ $tickerPost->title }}</span>
                                    <span class="whitespace-nowrap text-ink-400">{{ $tickerPost->published_at?->translatedFormat('j M') }}</span>
                                </a>
                            @endforeach
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if ($headline)
            <section aria-labelledby="headline-heading" class="grid gap-8 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <article class="group flex h-full flex-col">
                        <a href="{{ route('posts.show', $headline) }}" class="block aspect-[16/9] overflow-hidden rounded-lg">
                            <img src="{{ $headline->featuredImageUrl() }}"
                                 alt="{{ $headline->title }}"
                                 width="1200" height="675"
                                 loading="eager"
                                 class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.02]">
                        </a>
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            @if ($headline->is_featured)
                                <span class="eyebrow rounded-sm bg-accent-400 px-2 py-1 text-ink-950">Berita Pilihan</span>
                            @endif
                            <a href="{{ route('categories.show', $headline->category) }}" class="eyebrow text-accent-600 hover:text-accent-700">
                                {{ $headline->category->name }}
                            </a>
                        </div>
                        <h1 id="headline-heading" class="mt-3 font-serif text-3xl font-bold leading-[1.15] tracking-tight text-ink-950 text-balance sm:text-4xl lg:text-[2.75rem]">
                            <a href="{{ route('posts.show', $headline) }}" class="headline-link">{{ $headline->title }}</a>
                        </h1>
                        @if ($headline->excerpt)
                            <p class="mt-3 max-w-2xl text-base leading-relaxed text-ink-600">{{ $headline->excerpt }}</p>
                        @endif
                        <p class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-5 text-xs text-ink-500">
                            <span class="font-semibold text-ink-800">{{ $headline->author?->name }}</span>
                            <span aria-hidden="true">&middot;</span>
                            <time datetime="{{ $headline->published_at?->toIso8601String() }}">
                                {{ $headline->published_at?->translatedFormat('j F Y H:i') }}
                            </time>
                            <span aria-hidden="true">&middot;</span>
                            <span>{{ $headline->readingTime() }} menit baca</span>
                        </p>
                    </article>
                </div>

                <div class="lg:col-span-5">
                    <div class="flex items-center justify-between border-b border-ink-200 pb-3">
                        <h2 class="font-serif text-xl font-bold text-ink-950">Sorotan</h2>
                        <a href="{{ route('posts.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">Semua berita &rarr;</a>
                    </div>

                    @if ($secondary->isNotEmpty())
                        <div class="divide-y divide-ink-100">
                            @foreach ($secondary->take(3) as $post)
                                <div class="py-3 first:pt-4">
                                    <x-public.post-card :post="$post" variant="compact" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-5 rounded-lg border border-ink-100 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="eyebrow text-ink-950">Paling Dibaca</h2>
                            <span class="h-px flex-1 bg-ink-100 pl-4" aria-hidden="true"></span>
                        </div>
                        <ol class="mt-3 space-y-3">
                            @forelse ($popular as $index => $post)
                                <li class="flex gap-3">
                                    <span class="font-serif text-xl font-bold leading-none text-ink-200">
                                        {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('posts.show', $post) }}"
                                           class="font-serif text-sm font-bold leading-snug text-ink-900 hover:text-accent-600">
                                            {{ $post->title }}
                                        </a>
                                        <p class="mt-0.5 text-[11px] text-ink-500">{{ $post->views }} kali dibaca</p>
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-ink-500">Belum ada data kunjungan.</li>
                            @endforelse
                        </ol>
                    </div>
                </div>
            </section>
        @else
            <x-public.empty-state
                title="Belum ada berita yang diterbitkan"
                message="Berita yang sudah diterbitkan administrator akan tampil di halaman ini."
            />
        @endif
    </div>

    @if ($latest->isNotEmpty())
        <section aria-labelledby="latest-heading" class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
            <x-public.section-heading
                title="Berita Terbaru"
                href="{{ route('posts.index') }}"
                linkLabel="Lihat semua berita"
            />

            <div class="grid gap-8 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($latest as $post)
                            <x-public.post-card :post="$post" />
                        @endforeach
                    </div>
                </div>

                <aside class="flex h-full flex-col gap-8 lg:col-span-4">
                    <div class="flex flex-1 flex-col">
                        <div class="mb-4 flex items-end justify-between border-b border-ink-200 pb-3">
                            <h2 class="font-serif text-xl font-bold text-ink-950">Buletin Terbaru</h2>
                            <a href="{{ route('bulletins.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">Semua &rarr;</a>
                        </div>

                        @if ($bulletins->isNotEmpty())
                            <div class="space-y-4">
                                @foreach ($bulletins as $bulletin)
                                    <a href="{{ route('bulletins.show', $bulletin) }}" class="group flex gap-3 rounded-lg border border-ink-100 bg-white p-3 transition hover:border-ink-300">
                                        <img src="{{ $bulletin->coverUrl() }}" alt="Cover {{ $bulletin->title }}" loading="lazy"
                                             width="80" height="106"
                                             class="h-[96px] w-[72px] shrink-0 rounded object-cover">
                                        <div class="min-w-0">
                                            <p class="eyebrow text-accent-600">Edisi {{ $bulletin->edition_number }} &middot; {{ $bulletin->year }}</p>
                                            <p class="mt-1 line-clamp-2 font-serif text-sm font-bold leading-snug text-ink-950 group-hover:text-accent-600">
                                                {{ $bulletin->title }}
                                            </p>
                                            <p class="mt-1 text-[11px] text-ink-500">
                                                {{ $bulletin->published_at?->translatedFormat('j M Y') }}
                                            </p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-ink-200 px-4 py-6 text-center text-sm leading-relaxed text-ink-500">
                                Belum ada buletin yang diterbitkan.<br>Buletin yang terbit akan tampil di sini.
                            </p>
                        @endif
                    </div>

                    @if ($gallery->isNotEmpty())
                        <div>
                            <div class="mb-4 flex items-end justify-between border-b border-ink-200 pb-3">
                                <h2 class="font-serif text-xl font-bold text-ink-950">Galeri Dokumentasi</h2>
                                <a href="{{ route('galleries.index') }}" class="text-xs font-semibold text-accent-600 hover:text-accent-700">Semua &rarr;</a>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($gallery->take(6) as $item)
                                    <a href="{{ route('galleries.index') }}" class="group relative block overflow-hidden rounded">
                                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy"
                                             width="300" height="300"
                                             class="aspect-square w-full object-cover transition duration-500 group-hover:scale-105">
                                        <span class="absolute inset-0 bg-ink-950/0 transition group-hover:bg-ink-950/20"></span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </section>
    @endif

    @foreach ($categorySections as $section)
        <section aria-labelledby="category-{{ $section->id }}-heading" class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-ink-200 pb-3">
                <h2 id="category-{{ $section->id }}-heading" class="flex items-center gap-3 font-serif text-2xl font-bold tracking-tight text-ink-950">
                    <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                    {{ $section->name }}
                </h2>
                <a href="{{ route('categories.show', $section) }}" class="text-sm font-semibold text-accent-600 hover:text-accent-700">
                    Lihat rubrik &rarr;
                </a>
            </div>

            <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->posts as $post)
                    <x-public.post-card :post="$post" :show-excerpt="false" />
                @endforeach
            </div>
        </section>
    @endforeach
</x-layouts.public>
