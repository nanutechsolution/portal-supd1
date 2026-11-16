<x-layouts.public
    title="Buletin"
    :description="'Kumpulan buletin dan publikasi berkala '.$site['site_directorate'].'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow text-accent-300">Publikasi Berkala</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Buletin {{ $site['site_short_name'] }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">
                Buletin edisi terbaru dan arsip publikasi {{ $site['site_directorate'] }}. Setiap edisi dapat dibaca langsung atau diunduh dalam format PDF.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($years->isNotEmpty())
            <nav aria-label="Filter tahun" class="mb-8 flex flex-wrap items-center gap-2">
                <a href="{{ route('bulletins.index') }}"
                   class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition
                          {{ request('tahun') ? 'border-ink-200 bg-white text-ink-600 hover:border-ink-400' : 'border-ink-900 bg-ink-900 text-white' }}">
                    Semua tahun
                </a>
                @foreach ($years as $year)
                    <a href="{{ route('bulletins.index', ['tahun' => $year]) }}"
                       class="rounded-full border px-3.5 py-1.5 text-xs font-semibold transition
                              {{ (string) request('tahun') === (string) $year ? 'border-ink-900 bg-ink-900 text-white' : 'border-ink-200 bg-white text-ink-600 hover:border-ink-400' }}">
                        {{ $year }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($bulletins->isNotEmpty())
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($bulletins as $bulletin)
                    <article class="group flex flex-col overflow-hidden rounded-lg border border-ink-100 bg-white shadow-sm transition hover:shadow-md">
                        <a href="{{ route('bulletins.show', $bulletin) }}" class="relative block overflow-hidden bg-ink-950">
                            <img src="{{ $bulletin->coverUrl() }}" alt="Cover {{ $bulletin->title }}" loading="lazy"
                                 width="600" height="800"
                                 class="aspect-[3/4] w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                            <span class="absolute left-3 top-3 rounded-sm bg-accent-400 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-ink-950">
                                Edisi {{ $bulletin->edition_number }}
                            </span>
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            <p class="eyebrow text-accent-600">Tahun {{ $bulletin->year }}</p>
                            <h2 class="mt-2 font-serif text-lg font-bold leading-snug text-ink-950">
                                <a href="{{ route('bulletins.show', $bulletin) }}" class="headline-link">{{ $bulletin->title }}</a>
                            </h2>
                            @if ($bulletin->description)
                                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink-600">{{ $bulletin->description }}</p>
                            @endif
                            <p class="mt-3 text-xs text-ink-500">
                                {{ $bulletin->published_at?->translatedFormat('j F Y') ?? 'Belum ada tanggal publikasi' }}
                            </p>
                            <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                <a href="{{ route('bulletins.show', $bulletin) }}"
                                   class="rounded bg-ink-900 px-3 py-2 text-xs font-semibold text-white hover:bg-ink-800">
                                    Lihat detail
                                </a>
                                @if ($bulletin->hasFile())
                                    <a href="{{ route('bulletins.download', $bulletin) }}"
                                       class="rounded border border-ink-200 px-3 py-2 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                                        Unduh PDF
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{ $bulletins->links('partials.pagination') }}
        @else
            <x-public.empty-state
                title="Belum ada buletin"
                message="Buletin yang sudah diterbitkan akan tampil di halaman ini."
            />
        @endif
    </div>
</x-layouts.public>
