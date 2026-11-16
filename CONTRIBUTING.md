# Panduan Kontribusi

Terima kasih telah membantu mengembangkan Portal SUPD I. Dokumen ini berisi alur
kerja yang harus diikuti untuk setiap kontribusi.

## Alur kerja

1. **Diskusikan dulu** — untuk perubahan besar (fitur baru, perubahan skema database,
   penghapusan fitur), diskusikan dengan penanggung jawab sebelum membuat commit.
2. **Buat branch** dari `main` dengan nama yang jelas:
   ```
   feat/filter-arsip-berita
   fix/login-throttle
   docs/panduan-operasional
   ```
3. **Kembangkan** dengan mengikuti konvensi proyek (lihat di bawah).
4. **Verifikasi** sebelum commit:
   ```bash
   vendor/bin/pint          # format kode
   php artisan test         # seluruh suite harus hijau
   ```
5. **Commit** dengan pesan yang deskriptif (Bahasa Indonesia atau Inggris), gaya
   [Conventional Commits](https://www.conventionalcommits.org/):
   ```
   feat: tambah filter tahun pada arsip berita
   fix: perbaiki redirect setelah login gagal
   docs: lengkapi panduan operasional
   ```
6. **Ajukan perubahan** melalui pull request (bila tersedia) atau kirim ke penanggung jawab repository.

## Konvensi proyek

- Ikuti gaya kode yang sudah ada; gunakan `vendor/bin/pint` sebelum commit
- Gunakan fitur Laravel standar: `php artisan make:`, Eloquent, Form Request, Policy,
  dan antrean untuk pekerjaan berat
- Filament: gunakan hook dan panel resmi, jangan override otorisasi dengan metode
  yang tidak didukung framework — gunakan Gate/Policy
- Database: jangan ubah struktur tanpa migration dan diskusi
- Rahasia: **tidak pernah** commit `.env`, kredensial, token, atau data pribadi
- Dokumen audit/remediation/investigasi adalah dokumen internal — jangan commit;
  simpan di luar repositori (sudah di-ignore)

## Pengujian

- Setiap perbaikan bug wajib menyertakan tes yang gagal sebelum perbaikan dan lulus sesudahnya
- Tes ditempatkan di `tests/Feature` (kebanyakan) atau `tests/Unit`
- Gunakan factory untuk data uji; SQLite in-memory dipakai otomatis oleh `phpunit.xml`
- Jalankan tes terkait dengan: `php artisan test --filter=NamaTest`

## Dokumentasi

- Perbarui README bila menambah fitur, perintah, atau variabel lingkungan baru
- Catat perubahan penting pada `CHANGELOG.md`
- Screenshot halaman baru/berubah: simpan di `docs/images/` dan rujuk dari README

## Keamanan

Temuan celah keamanan **bukan** untuk diperbaiki diam-diam di branch publik.
Laporkan sesuai [SECURITY.md](SECURITY.md).
