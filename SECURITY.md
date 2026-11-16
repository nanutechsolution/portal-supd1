# Kebijakan Keamanan

Kami menanggapi laporan keamanan terhadap Portal SUPD I dengan serius.

## Cara melaporkan kerentanan

1. **Jangan** membuka issue publik, pull request, atau membahas kerentanan di forum terbuka.
2. Laporkan langsung kepada Administrator Direktorat SUPD I melalui saluran resmi direktorat
   (surat resmi atau email ke alamat kontak resmi direktorat, sebagaimana tercantum pada
   halaman Profil situs publik), dengan subjek: **"Laporan Keamanan Portal SUPD I"**.
3. Sertakan:
   - Deskripsi singkat kerentanan dan dampaknya
   - Langkah reproduksi yang jelas
   - URL/halaman yang terdampak
   - Bukti konsep (jika ada) — tanpa mengeksploitasi data nyata pihak lain

## Lingkup

- Aplikasi portal (situs publik dan panel admin)
- Infrastruktur yang dikelola Direktorat (sepanjang diketahui pelapor)

## Harapan kami kepada pelapor

- Bertindak dengan itikad baik: jangan mengakses, memodifikasi, atau menghapus data yang bukan milik Anda
- Jangan melakukan denial-of-service atau mengganggu layanan pengguna lain
- Berikan waktu yang wajar untuk perbaikan sebelum publikasi apa pun

## Komitmen kami

- Konfirmasi penerimaan laporan secepatnya (target 2 hari kerja)
- Penilaian dan penanganan berdasarkan tingkat keparahan
- Pemberitahuan kembali kepada pelapor setelah perbaikan tersedia
- Kredit untuk pelapor (jika dikehendaki) setelah perbaikan dirilis

## Praktik keamanan yang sudah diterapkan

- Autentikasi panel dengan peran `admin` dan `editor`
- Otorisasi berbasis policy Laravel (`UserPolicy`, `AuditLogPolicy`) untuk halaman Sistem
- Audit log aktivitas sensitif (login, perubahan konten, ekspor) dengan IP & user agent
- Redaksi nilai rahasia pada audit log (token, password, api_key, dan sejenisnya tidak pernah tercatat)
- Validasi integritas berkas unggahan dan sanitisasi CSV (anti formula injection)
- Throttle pada pencarian, formulir kontak, dan unduhan buletin
- Caching halaman publik hanya untuk pengunjung anonim
- Preferensi tampilan divalidasi dengan whitelist di sisi server

## Versi yang didukung

Hanya rilis terbaru pada branch `main` yang menerima perbaikan keamanan.
