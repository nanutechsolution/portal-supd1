<x-layouts.public
    title="Galeri"
    :description="'Dokumentasi kegiatan '.$site['site_directorate'].'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="eyebrow text-accent-300">Dokumentasi</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Galeri Kegiatan</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">
                Dokumentasi foto kegiatan dan publikasi {{ $site['site_directorate'] }}. Klik foto untuk melihat versi lebih besar.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($items->isNotEmpty())
            <div class="columns-1 gap-5 sm:columns-2 lg:columns-3 [&>*]:mb-5">
                @foreach ($items as $item)
                    <figure class="break-inside-avoid overflow-hidden rounded-lg border border-ink-100 bg-white">
                        <button type="button"
                                data-lightbox-trigger
                                data-src="{{ $item->imageUrl() }}"
                                data-title="{{ $item->title }}"
                                class="group block w-full text-left"
                                aria-label="Perbesar foto: {{ $item->title }}">
                            <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy"
                                 width="800" height="600"
                                 class="w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                        </button>
                        <figcaption class="p-4">
                            <p class="font-serif text-sm font-bold leading-snug text-ink-950">{{ $item->title }}</p>
                            @if ($item->description)
                                <p class="mt-1 text-xs leading-relaxed text-ink-600">{{ $item->description }}</p>
                            @endif
                            @if ($item->documented_at)
                                <p class="mt-2 text-[11px] text-ink-400">
                                    {{ $item->documented_at->translatedFormat('j F Y') }}
                                </p>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>

            {{ $items->links('partials.pagination') }}
        @else
            <x-public.empty-state
                title="Belum ada foto dokumentasi"
                message="Foto kegiatan yang diunggah administrator akan tampil di halaman ini."
            />
        @endif
    </div>

    <div data-lightbox class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-950/95 p-4">
        <button type="button" data-lightbox-close
                class="absolute right-4 top-4 rounded-full border border-white/30 p-2 text-white hover:bg-white/10"
                aria-label="Tutup pratinjau">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
        <figure class="max-h-full max-w-5xl">
            <img data-lightbox-image src="" alt="" class="mx-auto max-h-[80vh] w-auto rounded object-contain">
            <figcaption data-lightbox-caption class="mt-4 text-center text-sm text-ink-200"></figcaption>
        </figure>
    </div>
</x-layouts.public>
