@php
    $periodLabel = $periodLabel ?? null;
    $category = $category ?? null;
    $author = $author ?? null;

    $filterQuery = array_filter([
        'kategori' => $category?->slug,
        'penulis' => $author?->getKey(),
    ], fn ($value) => $value !== null);

    $periodQuery = array_filter([
        'tahun' => $year ?? null,
        'bulan' => $month ?? null,
    ], fn ($value) => $value !== null);

    $activeQuery = array_merge($periodQuery, $filterQuery);
    $canonical = route('archive.index', $activeQuery);
    $hasPeriod = filled($year ?? null);
    $isFiltering = $hasPeriod || $category !== null || $author !== null;

    $titleParts = ['Arsip Berita'];

    if ($category !== null) {
        $titleParts[] = 'Rubrik '.$category->name;
    }

    if ($author !== null) {
        $titleParts[] = 'Penulis '.$author->name;
    }

    if ($periodLabel) {
        $titleParts[] = $periodLabel;
    }

    $title = implode(' — ', $titleParts);

    $description = 'Kumpulan berita yang diterbitkan'
        .($category !== null ? ' pada rubrik '.$category->name : '')
        .($author !== null ? ' oleh '.$author->name : '')
        .($periodLabel ? ' pada '.$periodLabel : '')
        .', dikelompokkan per tahun dan bulan.';
@endphp

<x-layouts.public
    :title="$title"
    :description="$description"
    :canonical="$canonical"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-400">
                <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('archive.index') }}" class="hover:text-white">Arsip Berita</a>
                @if ($category !== null)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('archive.index', ['kategori' => $category->slug]) }}" class="hover:text-white">{{ $category->name }}</a>
                @endif
                @if ($author !== null)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('archive.index', ['penulis' => $author->getKey()]) }}" class="hover:text-white">{{ $author->name }}</a>
                @endif
                @if ($periodLabel)
                    <span aria-hidden="true">/</span>
                    <span class="text-ink-200">{{ $periodLabel }}</span>
                @endif
            </nav>
            <p class="eyebrow mt-4 text-accent-300">Publikasi</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">{{ $title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">{{ $description }}</p>
            <p class="mt-4 text-xs text-ink-400">{{ $posts->total() }} berita{{ $isFiltering ? ' pada filter ini' : ' tersedia' }}</p>

            @if ($category !== null || $author !== null)
                <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                    @if ($category !== null)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3 py-1.5 text-ink-100">
                            Rubrik: <span class="font-semibold text-white">{{ $category->name }}</span>
                            <a href="{{ route('archive.index', array_filter(['tahun' => $year ?? null, 'bulan' => $month ?? null, 'penulis' => $author?->getKey()])) }}"
                               class="text-accent-300 hover:text-white" aria-label="Hapus filter rubrik">&times;</a>
                        </span>
                    @endif
                    @if ($author !== null)
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-3 py-1.5 text-ink-100">
                            Penulis: <span class="font-semibold text-white">{{ $author->name }}</span>
                            <a href="{{ route('archive.index', array_filter(['tahun' => $year ?? null, 'bulan' => $month ?? null, 'kategori' => $category?->slug])) }}"
                               class="text-accent-300 hover:text-white" aria-label="Hapus filter penulis">&times;</a>
                        </span>
                    @endif
                    <a href="{{ route('archive.index', $periodQuery) }}" class="font-semibold text-accent-300 hover:text-white">
                        Hapus filter rubrik/penulis
                    </a>
                </div>
            @endif
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-8">
            @if ($posts->isNotEmpty())
                <div class="grid gap-x-6 gap-y-9 sm:grid-cols-2">
                    @foreach ($posts as $post)
                        <x-public.post-card :post="$post" />
                    @endforeach
                </div>

                {{ $posts->links('partials.pagination') }}
            @else
                @php
                    $emptyTitle = match (true) {
                        $category !== null || $author !== null => 'Tidak ada berita yang cocok',
                        $hasPeriod => 'Tidak ada berita pada periode ini',
                        default => 'Belum ada berita',
                    };
                    $emptyMessage = match (true) {
                        $category !== null || $author !== null => 'Kombinasi rubrik, penulis, dan periode yang dipilih belum memiliki berita terbit.',
                        $hasPeriod => 'Belum ada berita yang diterbitkan pada '.$periodLabel.'.',
                        default => 'Berita yang sudah diterbitkan akan tampil di arsip ini.',
                    };
                    $emptyAction = $isFiltering ? route('archive.index') : route('posts.index');
                    $emptyActionLabel = $isFiltering ? 'Tampilkan semua arsip' : 'Lihat berita';
                @endphp
                <x-public.empty-state
                    :title="$emptyTitle"
                    :message="$emptyMessage"
                    :action="$emptyAction"
                    :action-label="$emptyActionLabel"
                />
            @endif
        </div>

        <aside class="space-y-6 lg:col-span-4">
            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-4 text-ink-950">Arsip</h2>

                @if ($archives->isNotEmpty())
                    <ul class="space-y-1">
                        @foreach ($archives as $tahun => $months)
                            @php
                                $yearInt = (int) $tahun;
                                $yearActive = $hasPeriod && $year === $yearInt && $month === null;
                                $yearParams = array_merge($filterQuery, ['tahun' => $yearInt]);
                            @endphp
                            <li>
                                <a href="{{ route('archive.index', $yearParams) }}"
                                   class="flex items-center justify-between gap-3 rounded px-2 py-2 text-sm transition
                                          {{ $yearActive ? 'bg-ink-50 font-semibold text-ink-950' : 'text-ink-800 hover:bg-ink-50 hover:text-ink-950' }}"
                                   @if ($yearActive) aria-current="true" @endif>
                                    <span>{{ $tahun }}</span>
                                    <span class="text-xs text-ink-500">{{ $totalByYear[$tahun] ?? 0 }} berita</span>
                                </a>
                                <ul class="ml-4 mt-1 space-y-1 border-l border-ink-100 pl-3">
                                    @foreach ($months as $period)
                                        @php
                                            $monthInt = (int) $period['bulan'];
                                            $monthLabel = \Carbon\CarbonImmutable::create($yearInt, $monthInt, 1)->translatedFormat('F');
                                            $monthActive = $hasPeriod && $year === $yearInt && $month === $monthInt;
                                            $monthParams = array_merge($filterQuery, ['tahun' => $yearInt, 'bulan' => $monthInt]);
                                        @endphp
                                        <li>
                                            <a href="{{ route('archive.index', $monthParams) }}"
                                               class="flex items-center justify-between gap-3 rounded px-2 py-1.5 text-[13px] transition
                                                      {{ $monthActive ? 'bg-ink-50 font-semibold text-ink-950' : 'text-ink-600 hover:bg-ink-50 hover:text-ink-950' }}"
                                               @if ($monthActive) aria-current="true" @endif>
                                                <span>{{ $monthLabel }}</span>
                                                <span class="text-[11px] text-ink-500">{{ $period['total'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>

                    @if ($hasPeriod)
                        <a href="{{ route('archive.index', $filterQuery) }}"
                           class="mt-4 inline-flex text-xs font-semibold text-accent-600 hover:text-accent-700">
                            Tampilkan semua periode &rarr;
                        </a>
                    @endif
                @else
                    <p class="text-sm text-ink-500">Belum ada berita dipublikasikan.</p>
                @endif
            </div>

            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-4 text-ink-950">Rubrik</h2>
                <ul class="space-y-1">
                    @foreach ($categories as $archiveCategory)
                        <li>
                            <a href="{{ route('archive.index', ['kategori' => $archiveCategory->slug]) }}"
                               class="flex items-center justify-between gap-3 rounded px-2 py-2 text-sm transition
                                      {{ $category?->is($archiveCategory) ? 'bg-ink-50 font-semibold text-ink-950' : 'text-ink-700 hover:bg-ink-50 hover:text-ink-950' }}">
                                <span>{{ $archiveCategory->name }}</span>
                                <span class="text-xs text-ink-400">{{ $archiveCategory->posts_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($authors->isNotEmpty())
                <div class="rounded-lg border border-ink-100 bg-white p-5">
                    <h2 class="eyebrow mb-4 text-ink-950">Penulis</h2>
                    <ul class="space-y-1">
                        @foreach ($authors as $archiveAuthor)
                            <li>
                                <a href="{{ route('archive.index', ['penulis' => $archiveAuthor->getKey()]) }}"
                                   class="flex items-center justify-between gap-3 rounded px-2 py-2 text-sm transition
                                          {{ $author?->is($archiveAuthor) ? 'bg-ink-50 font-semibold text-ink-950' : 'text-ink-700 hover:bg-ink-50 hover:text-ink-950' }}">
                                    <span>{{ $archiveAuthor->name }}</span>
                                    <span class="text-xs text-ink-400">{{ $archiveAuthor->posts_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg border border-ink-100 bg-white p-5">
                <h2 class="eyebrow mb-3 text-ink-950">Telusuri</h2>
                <a href="{{ route('posts.index') }}" class="text-sm font-semibold text-ink-700 hover:text-accent-600">
                    Berita terbaru &rarr;
                </a>
                <p class="mt-3 text-xs leading-relaxed text-ink-500">
                    Arsip diurutkan berdasarkan tanggal publikasi; berita lama tetap tersimpan dan dapat diakses kapan pun.
                </p>
            </div>
        </aside>
    </div>
</x-layouts.public>
