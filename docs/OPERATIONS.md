# Buku Operasi (Operations Runbook)

Panduan operasional produksi Portal Informasi dan Buletin Direktorat SUPD I.
Untuk instalasi awal, lihat [README.md](../README.md).

## Konfigurasi produksi minimum

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portal.supd1.…        # domain resmi, HTTPS wajib
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

LOG_CHANNEL=daily                     # rotasi log otomatis (14 hari)
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=…
DB_DATABASE=portal_supd1
DB_USERNAME=…
DB_PASSWORD=…

QUEUE_CONNECTION=database             # atau redis untuk beban lebih tinggi
CACHE_STORE=database                  # atau redis
MAIL_MAILER=smtp                      # notifikasi pesan kontak & laporan bulanan
MAIL_HOST=…
MAIL_USERNAME=…
MAIL_PASSWORD=…
```

Setelah mengubah `.env`:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## Backup database

Aplikasi menyertakan perintah backup yang aman untuk produksi:

```bash
php artisan backup:database               # manual, retensi 14 hari
php artisan backup:database --keep=30     # simpan 30 hari
```

- Terjadwal otomatis **harian pukul 02:30 WIB** (`routes/console.php`)
- Hasil disimpan di `storage/app/backups/` (diabaikan Git)
- Driver yang didukung:
  - `sqlite` — penyalinan berkas database langsung
  - `mysql`/`mariadb` — `mysqldump --single-transaction --quick --no-tablespaces` (password lewat `MYSQL_PWD`, tidak tampak di command line), output di-gzip
  - `pgsql` — `pg_dump --no-owner --no-privileges` (password lewat `PGPASSWORD`), output di-gzip
- Backup yang lebih tua dari `--keep` hari dihapus otomatis
- Setiap backup dicatat di log (`storage/logs/laravel.log`)

**Restore (MySQL)**:

```bash
gunzip -c storage/app/backups/portal-YYYYMMDD-HHMMSS.sql.gz | mysql -u USER -p portal_supd1
```

**Wajib**: simpan backup di luar server (backup off-site, mis. storage eksternal atau
server lain) — backup di server yang sama tidak melindungi dari kerusakan disk.

## Scheduler & queue worker

Scheduler wajib dijalankan setiap menit (cron):

```cron
* * * * * cd /var/www/portal-supd1 && php artisan schedule:run >> /dev/null 2>&1
```

Tugas terjadwal:

| Waktu | Perintah | Fungsi |
|-------|----------|--------|
| tiap menit | `posts:publish-scheduled` | Audit penerbitan otomatis berita terjadwal |
| harian 02:30 | `backup:database` | Backup database + rotasi retensi |
| harian | `audit:prune` | Hapus audit log lama |
| bulanan (tgl 1, 07:00) | `reports:email-monthly` | Email laporan bulanan (xlsx) |

Queue worker (untuk email dan pekerjaan antrean):

```ini
# /etc/supervisor/conf.d/portal-supd1-worker.conf
[program:portal-supd1-worker]
command=php /var/www/portal-supd1/artisan queue:work --tries=3 --sleep=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=1
```

## Monitoring

- **Health check bawaan**: `GET /up` — mengembalikan 200 bila aplikasi hidup
  (siap dipakai uptime monitor seperti UptimeRobot/Better Stack)
- **Log**: `storage/logs/laravel-YYYY-MM-DD.log` (dengan `LOG_CHANNEL=daily`);
  pantau `ERROR`/`CRITICAL` secara rutin atau teruskan ke sistem pemantauan log
  terpusat (Loki, ELK, Papertrail) bila tersedia
- **Error tracking** (opsional): integrasikan Sentry atau Laravel Nightwatch —
  keduanya memerlukan dependensi/akun terpisah dan belum diaktifkan di repo ini
- **Disk**: pantau kapasitas `storage/app` (unggahan & backup) — habis disk =
  unggahan gagal dan backup gagal
- **Database**: pantau ukuran tabel `audit_logs` dan `post_view_statistics`
  (keduanya dipruner/di-roll-up otomatis; periksa bila membengkak)

## Keamanan operasional

- `APP_DEBUG=false` di produksi — stack trace tidak boleh bocor ke pengguna
- `ADMIN_PASSWORD` produksi harus kuat dan berbeda dari pengembangan; ganti segera
  setelah deployment pertama
- Jangan commit `.env` atau berkas backup; keduanya sudah diabaikan Git
- Batasi akses `/admin` di level web server bila memungkinkan (VPN/allowlist IP)
  sebagai lapis tambahan, bukan pengganti otorisasi aplikasi
- Rotasi kredensial database dan mail secara berkala

## Troubleshooting

| Gejala | Pemeriksaan pertama |
|--------|---------------------|
| 500 di semua halaman | `tail -50 storage/logs/laravel-*.log`; `APP_DEBUG=true` sementara di staging |
| Perubahan tidak tampil | `php artisan optimize:clear` lalu `config:cache route:cache view:cache` |
| Unggahan gagal | Izin `storage/` dan `bootstrap/cache`; sisa disk; `php artisan storage:link` |
| Gambar tidak muncul | Tautan `public/storage` ada? `php artisan storage:link` |
| Email tidak terkirim | `MAIL_*` benar? Antrean jalan? `php artisan queue:work` |
| Backup gagal | `mysqldump`/`pg_dump` terpasang di server? Izin folder `storage/app/backups`? |
| Cron tidak berjalan | `crontab -l`; path aplikasi benar; `php artisan schedule:run` manual |
