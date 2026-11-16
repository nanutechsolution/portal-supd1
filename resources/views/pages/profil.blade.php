<x-layouts.public
    title="Profil"
    :description="'Profil, tugas dan fungsi, serta kontak '.$site['site_directorate'].'.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="eyebrow text-accent-300">Profil</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">
                {{ $site['site_directorate'] }}
            </h1>
            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-ink-300">
                {{ $site['site_tagline'] }}
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="space-y-10 lg:col-span-8">
            <section aria-labelledby="tentang-heading">
                <div class="mb-5 flex items-center gap-3 border-b border-ink-200 pb-3">
                    <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                    <h2 id="tentang-heading" class="font-serif text-2xl font-bold text-ink-950">
                        {{ $about?->title ?? 'Tentang SUPD I' }}
                    </h2>
                </div>
                @if ($about?->content)
                    <div class="article-body">{!! $about->content !!}</div>
                @else
                    <p class="rounded-lg border border-dashed border-ink-200 px-5 py-8 text-sm text-ink-500">
                        Uraian profil belum tersedia. Administrator dapat melengkapinya melalui panel admin.
                    </p>
                @endif
            </section>

            <section aria-labelledby="tugas-heading">
                <div class="mb-5 flex items-center gap-3 border-b border-ink-200 pb-3">
                    <span class="inline-block h-6 w-1 rounded-full bg-accent-400" aria-hidden="true"></span>
                    <h2 id="tugas-heading" class="font-serif text-2xl font-bold text-ink-950">
                        {{ $duties?->title ?? 'Tugas dan Fungsi' }}
                    </h2>
                </div>
                @if ($duties?->content)
                    <div class="article-body">{!! $duties->content !!}</div>
                @else
                    <p class="rounded-lg border border-dashed border-ink-200 px-5 py-8 text-sm text-ink-500">
                        Uraian tugas dan fungsi belum tersedia. Administrator dapat melengkapinya melalui panel admin.
                    </p>
                @endif
            </section>
        </div>

        <aside class="lg:col-span-4">
            <div id="kontak" class="rounded-lg border border-ink-100 bg-white p-6">
                <h2 class="font-serif text-xl font-bold text-ink-950">Kontak</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    Informasi kontak resmi direktorat.
                </p>

                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="eyebrow text-ink-400">Alamat</dt>
                        <dd class="mt-1 leading-relaxed text-ink-700">
                            {{ $site['contact_address'] ?: 'Alamat kantor belum tersedia.' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-ink-400">Email</dt>
                        <dd class="mt-1 text-ink-700">
                            @if ($site['contact_email'])
                                <a href="mailto:{{ $site['contact_email'] }}" class="text-accent-600 hover:underline">{{ $site['contact_email'] }}</a>
                            @else
                                Belum tersedia.
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-ink-400">Telepon</dt>
                        <dd class="mt-1 text-ink-700">
                            @if ($site['contact_phone'])
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="text-accent-600 hover:underline">{{ $site['contact_phone'] }}</a>
                            @else
                                Belum tersedia.
                            @endif
                        </dd>
                    </div>
                    @if ($site['site_parent_url'])
                        <div>
                            <dt class="eyebrow text-ink-400">Website Induk</dt>
                            <dd class="mt-1 text-ink-700">
                                <a href="{{ $site['site_parent_url'] }}" target="_blank" rel="noopener noreferrer" class="text-accent-600 hover:underline">
                                    {{ $site['site_parent_name'] ?: $site['site_parent_url'] }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($site['social_instagram'] || $site['social_facebook'] || $site['social_youtube'] || $site['social_x'])
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-4">
                        @if ($site['social_instagram'])
                            <a href="{{ $site['social_instagram'] }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400">
                                Instagram
                            </a>
                        @endif
                        @if ($site['social_facebook'])
                            <a href="{{ $site['social_facebook'] }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400">
                                Facebook
                            </a>
                        @endif
                        @if ($site['social_youtube'])
                            <a href="{{ $site['social_youtube'] }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400">
                                YouTube
                            </a>
                        @endif
                        @if ($site['social_x'])
                            <a href="{{ $site['social_x'] }}" target="_blank" rel="noopener noreferrer"
                               class="rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 hover:border-ink-400">
                                X
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-6 rounded-lg bg-ink-950 p-6 text-ink-200">
                <p class="eyebrow text-accent-300">Publikasi</p>
                <p class="mt-2 font-serif text-lg font-bold text-white">Buletin &amp; dokumentasi</p>
                <p class="mt-2 text-sm leading-relaxed text-ink-300">
                    Telusuri publikasi berkala dan dokumentasi kegiatan direktorat.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('bulletins.index') }}" class="rounded bg-accent-400 px-3 py-2 text-xs font-bold text-ink-950 hover:bg-accent-300">
                        Lihat buletin
                    </a>
                    <a href="{{ route('galleries.index') }}" class="rounded border border-white/30 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10">
                        Galeri
                    </a>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.public>
