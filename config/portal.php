<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Portal
    |--------------------------------------------------------------------------
    |
    | Nilai di bawah ini menjadi identitas dasar portal. Sebagian dapat
    | ditimpa administrator melalui menu "Pengaturan Website". Nilai kosong
    | sengaja dibiarkan sebagai placeholder sampai data resmi tersedia.
    |
    */

    'settings' => [
        'site_name' => 'Portal Informasi dan Buletin Direktorat SUPD I',
        'site_short_name' => 'SUPD I',
        'site_directorate' => 'Direktorat Sinkronisasi Urusan Pemerintahan Daerah I',
        'site_ministry_line1' => 'Direktorat Jenderal Bina Pembangunan Daerah',
        'site_ministry_line2' => 'Kementerian Dalam Negeri',
        'site_portal_label' => 'Portal Informasi dan Buletin',
        'site_description' => 'Portal informasi, buletin, dan dokumentasi kegiatan Direktorat SUPD I.',
        'site_tagline' => 'Kanal publikasi berita, buletin, dan dokumentasi Direktorat Sinkronisasi Urusan Pemerintahan Daerah I.',
        'site_logo' => '',
        'site_logo_footer' => '',
        'site_favicon' => '',
        'contact_address' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'site_parent_name' => '',
        'site_parent_url' => '',
        'social_instagram' => '',
        'social_facebook' => '',
        'social_youtube' => '',
        'social_x' => '',
        'seo_title' => '',
        'seo_description' => '',
        'seo_og_image' => '',
        'seo_social_image' => '',
        'theme_primary' => '#0b2836',
        'theme_secondary' => '#dda43a',
        'footer_copyright' => 'Direktorat Sinkronisasi Urusan Pemerintahan Daerah I',
        'show_header_topbar' => '1',
        'show_header_search' => '1',
        'show_footer_logo' => '1',
        'show_footer_contact' => '1',
        'show_footer_socials' => '1',
        'homepage_news_count' => '6',
        'posts_per_page' => '9',
        'popular_posts_count' => '5',
        'breaking_news_enabled' => '1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Tampilan
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'posts' => 9,
        'bulletins' => 9,
        'gallery' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kontak
    |--------------------------------------------------------------------------
    |
    | Alamat tujuan notifikasi pesan kontak. Isian "contact_email" pada
    | Pengaturan Website dipakai lebih dulu; nilai ini menjadi fallback
    | ketika pengaturan tersebut belum diisi.
    |
    */

    'contact' => [
        'notification_email' => env('ADMIN_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Halaman Publik
    |--------------------------------------------------------------------------
    |
    | HTML halaman daftar/statis di-cache untuk pengunjung anonim. Cache
    | otomatis tidak berlaku ketika konten berubah dan berganti kunci tepat
    | saat berita terjadwal memasuki waktu tayang.
    |
    */

    'public_cache' => [
        'enabled' => (bool) env('PUBLIC_PAGE_CACHE', true),
        'ttl' => (int) env('PUBLIC_PAGE_CACHE_TTL', 300),
    ],

];
