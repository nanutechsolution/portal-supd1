# Changelog

Seluruh perubahan penting pada Portal Informasi dan Buletin Direktorat SUPD I
didokumentasikan di berkas ini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/)
dan proyek ini menggunakan [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] - 2026-11-16

Rilis awal portal resmi.

### Added

**Situs publik**
- Beranda dengan rubrik terpilih, sorotan terbaru, dan berita pilihan
- Daftar berita per rubrik/satuan kerja, detail artikel (prev/next, terkait, estimasi waktu baca, hitung kunjungan)
- Arsip berita per tahun dan bulan
- Halaman rubrik (kategori)
- Buletin: daftar edisi, lihat di browser, unduh dengan throttle
- Galeri dokumentasi kegiatan
- Pencarian global (berita, buletin, halaman) dengan throttle
- Halaman profil “Tentang SUPD I” dan bagan Struktur Organisasi
- Formulir kontak dengan notifikasi email ke administrator
- RSS (`rss.xml`, `feed`), `sitemap.xml`, `robots.txt`
- Caching halaman publik penuh untuk pengunjung anonim (dapat diatur via `PUBLIC_PAGE_CACHE`)

**Panel admin (`/admin`, Filament 5)**
- Dasbor ringkasan (berita terbit, draf, terjadwal, kunjungan, kategori, buletin, galeri)
- Grup navigasi **Publikasi**: Berita (draf/terbit, terjadwal, preview, berita pilihan), Kategori Berita, Buletin, Galeri
- Grup navigasi **Situs**: Pesan Kontak, Halaman Profil, Struktur Organisasi
- Grup navigasi **Sistem**: Pengguna (Administrator/Editor), Pengaturan Website, Analitik, Laporan, Tampilan, Audit Log — dibatasi untuk Administrator
- Ekspor laporan ke Excel (.xlsx) dan CSV (daftar berita, statistik publikasi/kunjungan, terpopuler, distribusi kategori)
- Preferensi tampilan personal per pengguna: mode (Terang/Gelap/Sistem), warna aksen, kepadatan, bilah samping, dan **warna sidebar independen** (dirender server-side tanpa kedipan tema)
- Audit log aktivitas sensitif dengan redaksi nilai rahasia, before/after, IP, dan user agent
- Otomasi terjadwal: `posts:publish-scheduled` (tiap menit), `audit:prune` (harian), `reports:email-monthly` (bulanan)

**Kualitas & operasional**
- Pengujian fitur PHPUnit (portal publik, panel admin, keamanan & otorisasi)
- Laravel Pint untuk konsistensi gaya kode
- CI GitHub Actions (build, Pint, seluruh suite pengujian)
- Backup database terjadwal: `backup:database` harian (02:30 WIB) dengan rotasi retensi
- Buku operasi produksi: `docs/OPERATIONS.md` (scheduler, queue, monitoring `/up`, troubleshooting)
- README lengkap, LICENSE, SECURITY.md, CONTRIBUTING.md, CHANGELOG.md

### Security

- Otorisasi halaman Sistem berbasis policy Laravel (`UserPolicy`, `AuditLogPolicy` — audit log baca-saja untuk Administrator)
- Validasi integritas berkas unggahan dan sanitisasi CSV terhadap formula injection
- Redaksi kredensial/token pada audit log; kredensial tidak pernah tercatat
- Preferensi tampilan divalidasi whitelist di sisi server
- RSS (`rss.xml`, `feed`) dan `sitemap.xml` kini dibatasi 30 permintaan per menit per IP
