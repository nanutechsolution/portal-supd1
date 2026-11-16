@php
    $socials = collect([
        'Instagram' => $site['social_instagram'] ?? null,
        'Facebook' => $site['social_facebook'] ?? null,
        'YouTube' => $site['social_youtube'] ?? null,
        'X' => $site['social_x'] ?? null,
    ])->filter();

    $footerLogo = \App\Models\Setting::mediaUrl('site_logo_footer')
        ?: \App\Models\Setting::mediaUrl('site_logo');
    $showFooterLogo = \App\Models\Setting::bool('show_footer_logo', true);
    $showFooterContact = \App\Models\Setting::bool('show_footer_contact', true);
    $showFooterSocials = \App\Models\Setting::bool('show_footer_socials', true);
    $parentUrl = filled($site['site_parent_url'] ?? null) ? $site['site_parent_url'] : null;
    $parentName = filled($site['site_parent_name'] ?? null) ? $site['site_parent_name'] : $parentUrl;
    $copyright = rtrim(str_replace('{year}', (string) now()->year, (string) ($site['footer_copyright'] ?: $site['site_directorate'])), '.');
@endphp

<footer class="mt-20 border-t-4 border-accent-400 bg-ink-950 text-ink-200">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-5">
            <div class="flex items-center gap-3">
                @if ($showFooterLogo)
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 p-1.5">
                        @if ($footerLogo)
                            <img src="{{ $footerLogo }}" alt="Logo {{ $site['site_short_name'] }}"
                                 class="h-full w-full object-contain">
                        @else
                            <x-public.logo />
                        @endif
                    </span>
                @endif
                <div>
                    <p class="font-serif text-lg font-bold text-white">{{ $site['site_short_name'] }}</p>
                    <p class="text-xs text-ink-300">{{ $site['site_portal_label'] ?: 'Portal Informasi' }}</p>
                </div>
            </div>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-ink-300">
                {{ $site['site_description'] ?: $site['site_tagline'] }}
            </p>
            <p class="mt-4 text-xs text-ink-400">
                {{ $site['site_directorate'] }}
            </p>
        </div>

        <div class="lg:col-span-3">
            <p class="eyebrow mb-4 text-accent-300">Rubrik</p>
            <ul class="space-y-2 text-sm">
                @foreach ($navCategories->take(7) as $category)
                    <li>
                        <a href="{{ route('categories.show', $category) }}" title="{{ $category->name }}" class="text-ink-300 transition hover:text-white">
                            {{ $category->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-2">
            <p class="eyebrow mb-4 text-accent-300">Tautan</p>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('posts.index') }}" class="text-ink-300 hover:text-white">Berita</a></li>
                <li><a href="{{ route('archive.index') }}" class="text-ink-300 hover:text-white">Arsip Berita</a></li>
                <li><a href="{{ route('bulletins.index') }}" class="text-ink-300 hover:text-white">Buletin</a></li>
                <li><a href="{{ route('galleries.index') }}" class="text-ink-300 hover:text-white">Galeri</a></li>
                <li><a href="{{ route('structure.index') }}" class="text-ink-300 hover:text-white">Struktur Organisasi</a></li>
                <li><a href="{{ route('pages.profil') }}" class="text-ink-300 hover:text-white">Profil</a></li>
                <li><a href="{{ route('contact.index') }}" class="text-ink-300 hover:text-white">Kontak</a></li>
            </ul>
        </div>

        @if ($showFooterContact)
            <div class="lg:col-span-2">
                <p class="eyebrow mb-4 text-accent-300">Kontak</p>
                <address class="space-y-2 text-sm not-italic text-ink-300">
                    @if ($site['contact_address'])
                        <p class="leading-relaxed">{{ $site['contact_address'] }}</p>
                    @endif
                    @if ($site['contact_email'])
                        <p><a href="mailto:{{ $site['contact_email'] }}" class="hover:text-white">{{ $site['contact_email'] }}</a></p>
                    @endif
                    @if ($site['contact_phone'])
                        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="hover:text-white">{{ $site['contact_phone'] }}</a></p>
                    @endif
                    @if ($parentUrl)
                        <p>
                            <a href="{{ $parentUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-white">
                                {{ $parentName }}
                            </a>
                        </p>
                    @endif
                    @if (! $site['contact_address'] && ! $site['contact_email'] && ! $site['contact_phone'] && ! $parentUrl)
                        <p class="text-ink-400">Informasi kontak belum tersedia.</p>
                    @endif
                </address>

                @if ($showFooterSocials && $socials->isNotEmpty())
                    <ul class="mt-4 flex flex-wrap gap-3 text-xs">
                        @foreach ($socials as $label => $url)
                            <li>
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-accent-300 hover:text-white">
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-ink-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ $copyright }}.</p>
            <p>{{ $site['site_portal_label'] ?: 'Portal publikasi berita, buletin, dan dokumentasi.' }}</p>
        </div>
    </div>
</footer>
