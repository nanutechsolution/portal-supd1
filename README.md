# Portal Informasi SUPD I

Portal informasi resmi Direktorat SUPD I — berisi berita, kategori, buletin, galeri,
halaman profil, struktur organisasi, kontak, dan pelaporan bulanan. Panel admin dibangun dengan
[Filament](https://filamentphp.com) dan [Livewire](https://livewire.laravel.com).

## Tech Stack

- **Laravel 13** (PHP ^8.3)
- **Filament 5** — panel admin (`/admin`)
- **Livewire 4** — interaktivitas
- **Tailwind CSS 4** — styling
- **PHPUnit 11** — pengujian
- **SQLite** (default) — dapat dialihkan ke MySQL/PostgreSQL lewat `.env`

## Persyaratan

- PHP 8.3+
- Composer
- Node.js + npm

## Setup

```bash
composer setup
```

Perintah di atas setara dengan:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

Buat database SQLite terlebih dahulu bila belum ada:

```bash
touch database/database.sqlite
```

Isi database dengan data awal (opsional):

```bash
php artisan db:seed
```

Seeder membuat akun administrator awal dari variabel lingkungan berikut:

| Variabel       | Default              |
|----------------|----------------------|
| `ADMIN_EMAIL`  | `admin@supd1.local`  |
| `ADMIN_NAME`   | `Administrator SUPD I` |
| `ADMIN_PASSWORD` | `password`         |

> **Jangan** gunakan kredensial default di produksi. Atur `ADMIN_PASSWORD` di `.env` sebelum seeding.

## Menjalankan aplikasi

```bash
composer dev
```

atau secara manual:

```bash
php artisan serve
npm run dev
```

- Situs publik: `http://localhost:8000`
- Panel admin: `http://localhost:8000/admin`
- Pengaturan tampilan: `http://localhost:8000/admin/tampilan`

## Pengujian

```bash
php artisan test
```

atau:

```bash
composer test
```

## Fitur Appearance Settings

Pengguna admin dapat mengatur tampilan panel secara personal di **Admin → Tampilan**
(`/admin/tampilan`). Preferensi disimpan per pengguna, bertahan setelah logout/login, dan
dirasakan konsisten di seluruh halaman admin:

- **Mode tampilan**: Terang / Gelap / Ikuti sistem
- **Warna aksen**: palet warna untuk tombol utama, tautan aktif, dan sorotan antarmuka
- **Tema (density)**: nyaman / ringkas — mengendalikan kerapatan spacing tabel, tombol, dan kartu
- **Bilah samping**: diperluas / diciutkan di desktop
- **Warna sidebar**: terpisah dari warna aksen; teks dan ikon tetap kontras pada mode terang maupun gelap

Preferensi divalidasi dengan whitelist nilai yang aman dan diterapkan server-side sebelum
first paint, sehingga tidak ada kedipan tema yang salah.

## Struktur utama

| Path | Keterangan |
|------|-----------|
| `app/Filament/Pages/AppearanceSettings.php` | Halaman pengaturan tampilan admin |
| `app/Support/Appearance.php` | Model preferensi + whitelist & palet warna |
| `app/Http/Middleware/ApplyUserAppearance.php` | Injeksi preferensi ke request |
| `app/Providers/Filament/AdminPanelProvider.php` | Hook render panel admin |
| `resources/views/filament/appearance/` | View style & skrip no-flash |
| `resources/views/filament/pages/appearance-settings.blade.php` | UI halaman tampilan |
| `tests/Feature/AdminAppearanceTest.php` | Tes fitur appearance |

## Lisensi

Proyek ini bukan proyek open-source. Seluruh hak cipta dimiliki oleh SUPD I.
