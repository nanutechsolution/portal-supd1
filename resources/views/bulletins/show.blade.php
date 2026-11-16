<x-layouts.public
    :title="$bulletin->title"
    :description="$bulletin->description ?: 'Buletin edisi '.$bulletin->edition_number.' tahun '.$bulletin->year.'.'"
    :image="$bulletin->cover ? $bulletin->coverUrl() : null"
    type="article"
    :published-at="$bulletin->published_at"
>
    <div class="border-b border-ink-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
                <a href="{{ route('home') }}" class="hover:text-accent-600">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('bulletins.index') }}" class="hover:text-accent-600">Buletin</a>
                <span aria-hidden="true">/</span>
                <span class="text-ink-800">Edisi {{ $bulletin->edition_number }}</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <img src="{{ $bulletin->coverUrl() }}" alt="Cover {{ $bulletin->title }}"
                     width="600" height="800"
                     class="aspect-[3/4] w-full rounded-lg border border-ink-100 object-cover shadow-sm">

                @if ($bulletin->hasFile())
                    <a href="{{ route('bulletins.download', $bulletin) }}"
                       class="mt-5 flex w-full items-center justify-center gap-2 rounded bg-ink-900 px-4 py-3 text-sm font-semibold text-white hover:bg-ink-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0 4-4m-4 4-4-4M5 20h14"/>
                        </svg>
                        Unduh PDF
                    </a>
                @else
                    <p class="mt-5 rounded border border-dashed border-ink-200 px-4 py-3 text-center text-xs text-ink-500">
                        File PDF belum tersedia untuk edisi ini.
                    </p>
                @endif
            </div>

            <div class="lg:col-span-8">
                <div class="flex flex-wrap gap-2">
                    <span class="eyebrow rounded-sm bg-accent-100 px-2 py-1 text-accent-700">Edisi {{ $bulletin->edition_number }}</span>
                    <span class="eyebrow rounded-sm bg-ink-100 px-2 py-1 text-ink-700">Tahun {{ $bulletin->year }}</span>
                </div>

                <h1 class="mt-4 font-serif text-3xl font-bold leading-tight tracking-tight text-ink-950 sm:text-4xl">
                    {{ $bulletin->title }}
                </h1>

                <dl class="mt-6 grid gap-4 border-y border-ink-100 py-5 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="eyebrow text-ink-400">Tanggal Publikasi</dt>
                        <dd class="mt-1 font-medium text-ink-800">
                            {{ $bulletin->published_at?->translatedFormat('j F Y') ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-ink-400">Nomor Edisi</dt>
                        <dd class="mt-1 font-medium text-ink-800">{{ $bulletin->edition_number }}</dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-ink-400">Unduhan</dt>
                        <dd class="mt-1 font-medium text-ink-800">{{ $bulletin->downloads }} kali</dd>
                    </div>
                </dl>

                @if ($bulletin->description)
                    <div class="mt-6 text-base leading-relaxed text-ink-700">
                        {!! nl2br(e($bulletin->description)) !!}
                    </div>
                @endif

                <a href="{{ route('bulletins.index') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-ink-700 hover:text-accent-600">
                    <span aria-hidden="true">&larr;</span> Kembali ke daftar buletin
                </a>
            </div>
        </div>

        @if ($bulletin->hasFile())
            <section class="mt-12" aria-labelledby="preview-heading">
                <h2 id="preview-heading" class="mb-4 flex items-center gap-3 font-serif text-2xl font-bold text-ink-950">
                    <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                    Pratinjau PDF
                </h2>
                <div class="overflow-hidden rounded-lg border border-ink-200 bg-ink-50">
                    <iframe src="{{ route('bulletins.preview', $bulletin) }}"
                            title="Pratinjau {{ $bulletin->title }}"
                            class="h-[70vh] min-h-[480px] w-full"
                            loading="lazy"></iframe>
                </div>
                <p class="mt-3 text-xs text-ink-500">
                    Pratinjau tidak tampil? <a href="{{ route('bulletins.preview', $bulletin) }}" target="_blank" rel="noopener"
                       class="font-semibold text-accent-600 underline">Buka PDF di tab baru</a>.
                </p>
            </section>
        @endif

        @if ($others->isNotEmpty())
            <section class="mt-14" aria-labelledby="other-bulletins-heading">
                <h2 id="other-bulletins-heading" class="mb-5 flex items-center gap-3 font-serif text-2xl font-bold text-ink-950">
                    <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                    Buletin Lainnya
                </h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($others as $other)
                        <a href="{{ route('bulletins.show', $other) }}"
                           class="group flex items-center gap-3 rounded-lg border border-ink-100 bg-white p-3 hover:border-ink-300">
                            <img src="{{ $other->coverUrl() }}" alt="" loading="lazy" width="64" height="84"
                                 class="h-[84px] w-16 shrink-0 rounded object-cover">
                            <div class="min-w-0">
                                <p class="eyebrow text-accent-600">Edisi {{ $other->edition_number }} &middot; {{ $other->year }}</p>
                                <p class="mt-1 line-clamp-2 font-serif text-sm font-bold leading-snug text-ink-950 group-hover:text-accent-600">
                                    {{ $other->title }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
