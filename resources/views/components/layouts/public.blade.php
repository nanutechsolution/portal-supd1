@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'publishedAt' => null,
    'noindex' => false,
    'jsonLd' => null,
    'canonical' => null,
])

@php
    $siteName = $site['site_name'] ?: 'Portal Informasi';
    $shortName = $site['site_short_name'] ?: $siteName;
    $defaultTitle = filled($site['seo_title'] ?? null) ? $site['seo_title'] : $siteName;
    $metaTitle = $title ? $title.' — '.$shortName : $defaultTitle;
    $metaDescription = $description
        ?: ($site['seo_description'] ?: ($site['site_description'] ?: $site['site_tagline']));
    $canonical = $canonical ?: url()->current();

    $ogImage = $image ? url($image) : \App\Models\Setting::mediaUrl('seo_og_image');
    $socialImage = $image
        ? url($image)
        : (\App\Models\Setting::mediaUrl('seo_social_image') ?: $ogImage);
    $favicon = \App\Models\Setting::mediaUrl('site_favicon');
    $faviconType = $favicon && \Illuminate\Support\Str::endsWith(parse_url($favicon, PHP_URL_PATH) ?: '', '.webp')
        ? 'image/webp'
        : 'image/png';
    $themeStyle = \App\Models\Setting::themeStyle();
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <link rel="canonical" href="{{ $canonical }}">

    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow">
    @endif

    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @if ($publishedAt)
        <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
    @endif
    <meta name="twitter:card" content="{{ $socialImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    @if ($socialImage)
        <meta name="twitter:image" content="{{ $socialImage }}">
    @endif

    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" type="{{ $faviconType }}">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @endif
    <link rel="alternate" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">
    <link rel="alternate" type="application/rss+xml" title="{{ $siteName }} — Berita" href="{{ route('rss.feed') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($themeStyle !== '')
        <style>{!! $themeStyle !!}</style>
    @endif

    @if ($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
</head>
<body class="min-h-screen bg-paper text-ink-900 antialiased">
    <a href="#konten"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-accent-400 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-ink-950 focus:outline-none focus:ring-2 focus:ring-ink-950 focus:ring-offset-2">
        Lewati ke konten
    </a>

    @include('partials.header', ['navCategories' => $navCategories, 'site' => $site])

    <main id="konten" tabindex="-1" class="scroll-mt-28 focus:outline-none">
        {{ $slot }}
    </main>

    @include('partials.footer', ['navCategories' => $navCategories, 'site' => $site])
</body>
</html>
