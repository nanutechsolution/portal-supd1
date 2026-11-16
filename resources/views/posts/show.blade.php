@php
    $shareUrl = url()->current();
    $shareText = $post->title;
    $isPreview = $preview ?? false;
    $whatsappUrl = 'https://wa.me/?text='.rawurlencode($shareText.' '.$shareUrl);
    $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl);
    $xUrl = 'https://twitter.com/intent/tweet?'.http_build_query(['text' => $shareText, 'url' => $shareUrl]);
@endphp

<x-layouts.public
    :title="$post->title"
    :description="$description"
    :image="$post->featured_image ? $post->featuredImageUrl() : null"
    type="article"
    :published-at="$post->published_at"
    :json-ld="$jsonLd"
    :noindex="$isPreview"
>
    @if ($isPreview)
        <div class="border-b border-amber-300 bg-amber-50">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2.5 sm:px-6 lg:px-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800">Pratinjau Draft</p>
                <p class="text-xs text-amber-900">
                    Berita ini belum dipublikasikan. Hanya terlihat oleh admin/redaksi yang login.
                </p>
            </div>
        </div>
    @endif

    <div class="border-b border-ink-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-500">
                <a href="{{ route('home') }}" class="hover:text-accent-600">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('posts.index') }}" class="hover:text-accent-600">Berita</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('categories.show', $post->category) }}" class="hover:text-accent-600">{{ $post->category->name }}</a>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <div class="grid gap-12 lg:grid-cols-12">
            <article class="lg:col-span-8">
                <a href="{{ route('categories.show', $post->category) }}"
                   class="eyebrow inline-block rounded-sm bg-accent-100 px-2 py-1 text-accent-700 hover:bg-accent-200">
                    {{ $post->category->name }}
                </a>

                <h1 class="mt-4 font-serif text-3xl font-bold leading-[1.15] tracking-tight text-ink-950 text-balance sm:text-4xl lg:text-[2.9rem]">
                    {{ $post->title }}
                </h1>

                @if ($post->excerpt)
                    <p class="mt-4 max-w-3xl border-l-2 border-accent-300 pl-4 font-serif text-lg leading-relaxed text-ink-600">
                        {{ $post->excerpt }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-ink-100 py-3 text-xs text-ink-500">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-ink-900 font-serif text-sm font-bold text-accent-300">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->author?->name ?? 'S', 0, 1)) }}
                        </span>
                        <span class="font-semibold text-ink-800">
                            @if ($post->author)
                                <a href="{{ route('archive.index', ['penulis' => $post->author->getKey()]) }}" class="hover:text-accent-600">
                                    {{ $post->author->name }}
                                </a>
                            @else
                                {{ 'Redaksi '.$site['site_short_name'] }}
                            @endif
                        </span>
                    </div>
                    <span aria-hidden="true">&middot;</span>
                    <time datetime="{{ $post->published_at?->toIso8601String() }}">
                        {{ $post->published_at?->translatedFormat('j F Y, H:i') }} WIB
                    </time>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $post->readingTime() }} menit baca</span>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $post->views }} kali dibaca</span>
                </div>

                <figure class="mt-7">
                    <img src="{{ $post->featuredImageUrl() }}"
                         alt="{{ $post->title }}"
                         width="1200" height="675"
                         class="aspect-[16/9] w-full rounded-lg object-cover">
                    @if (! $post->featured_image)
                        <figcaption class="mt-2 text-xs text-ink-500">
                            Gambar utama belum tersedia. Administrator dapat mengunggah foto melalui panel.
                        </figcaption>
                    @endif
                </figure>

                <div class="article-body mt-8">
                    {!! $post->content !!}
                </div>

                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-ink-200 pt-6">
                    <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700 hover:text-accent-600">
                        <span aria-hidden="true">&larr;</span> Kembali ke daftar berita
                    </a>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="eyebrow text-ink-400">Bagikan</span>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            WhatsApp
                        </a>
                        <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            Facebook
                        </a>
                        <a href="{{ $xUrl }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            X
                        </a>
                        <button type="button" data-copy-link data-label="Salin tautan"
                                class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400 hover:text-ink-950">
                            Salin tautan
                        </button>
                    </div>
                </div>

                @if ($previous || $next)
                    <nav aria-label="Navigasi antar berita" class="mt-10 grid gap-4 border-t border-ink-200 pt-6 sm:grid-cols-2">
                        @if ($previous)
                            <a href="{{ route('posts.show', $previous) }}"
                               class="group rounded-lg border border-ink-100 bg-white p-4 transition hover:border-ink-300 hover:bg-paper">
                                <span class="eyebrow text-ink-400"><span aria-hidden="true">&larr;</span> Berita Sebelumnya</span>
                                <span class="mt-1.5 block font-serif text-sm font-bold leading-snug text-ink-900 group-hover:text-accent-600">
                                    {{ $previous->title }}
                                </span>
                                <span class="mt-1 block text-[11px] text-ink-500">
                                    {{ $previous->published_at?->translatedFormat('j M Y') }}
                                </span>
                            </a>
                        @else
                            <span aria-hidden="true"></span>
                        @endif

                        @if ($next)
                            <a href="{{ route('posts.show', $next) }}"
                               class="group rounded-lg border border-ink-100 bg-white p-4 transition hover:border-ink-300 hover:bg-paper sm:text-right">
                                <span class="eyebrow text-ink-400">Berita Berikutnya <span aria-hidden="true">&rarr;</span></span>
                                <span class="mt-1.5 block font-serif text-sm font-bold leading-snug text-ink-900 group-hover:text-accent-600">
                                    {{ $next->title }}
                                </span>
                                <span class="mt-1 block text-[11px] text-ink-500">
                                    {{ $next->published_at?->translatedFormat('j M Y') }}
                                </span>
                            </a>
                        @endif
                    </nav>
                @endif
            </article>

            <aside class="space-y-8 lg:col-span-4">
                <div class="rounded-lg border border-ink-100 bg-white p-5">
                    <h2 class="eyebrow mb-3 text-ink-950">Berita Terkait</h2>
                    @if ($related->isNotEmpty())
                        <div class="divide-y divide-ink-100">
                            @foreach ($related as $relatedPost)
                                <div class="py-3 first:pt-0 last:pb-0">
                                    <x-public.post-card :post="$relatedPost" variant="compact" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-ink-500">Belum ada berita lain pada rubrik ini.</p>
                    @endif
                </div>

                <div class="rounded-lg bg-ink-950 p-5 text-ink-200">
                    <p class="eyebrow text-accent-300">Rubrik</p>
                    <a href="{{ route('categories.show', $post->category) }}"
                       class="mt-2 block font-serif text-xl font-bold text-white hover:text-accent-300">
                        {{ $post->category->name }}
                    </a>
                    <p class="mt-2 text-sm leading-relaxed text-ink-300">
                        Lihat seluruh publikasi pada rubrik ini.
                    </p>
                    <a href="{{ route('categories.show', $post->category) }}"
                       class="mt-4 inline-flex text-sm font-semibold text-accent-300 hover:text-white">
                        Buka rubrik &rarr;
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
