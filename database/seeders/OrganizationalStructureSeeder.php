<?php

namespace Database\Seeders;

use App\Enums\OrganizationalLevel;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Struktur organisasi contoh untuk bagan publik Direktorat SUPD I.
 *
 * Hierarki: Direktur → Kepala Bagian Tata Usaha (dengan Kasubbag Tata Usaha),
 * tiga Kepala Kelompok Kerja, dan Jabatan Fungsional. Setiap Kapokja memiliki
 * dua Ketua Tim. Semua nama pejabat adalah fiktif dan hanya dipakai sebagai
 * data contoh.
 *
 * Seeder ini menggabungkan seluruh data struktur ke dalam satu alur yang
 * idempotent. Unit contoh versi lama dibersihkan lebih dulu agar tidak ada
 * unit orphan atau duplikat yang tertinggal. Foto contoh dibuat sebagai
 * monogram dan disimpan di disk `public`; Jabatan Fungsional sengaja tidak
 * memiliki foto sesuai desain bagan.
 */
class OrganizationalStructureSeeder extends Seeder
{
    /**
     * @var string
     */
    private const PHOTO_DIRECTORY = 'structure';

    private const PHOTO_WIDTH = 480;

    private const PHOTO_HEIGHT = 600;

    /**
     * Nama unit dari seeder versi sebelumnya yang harus dibersihkan.
     *
     * @var array<int, string>
     */
    private array $legacyNames = [
        'Jokowi',
        'Direktur',
        'Subbagian Tata Usaha',
        'Subdirektorat Pertanian Pangan',
        'Subdirektorat Lingkungan Hidup',
        'Subdirektorat Kehutanan',
        'Subdirektorat Pertanahan dan Penataan Ruang',
        'Subdirektorat Pertanian dan Pangan',
        'Substansi Energi Sumber Daya dan Mineral',
        'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
    ];

    /**
     * @var array<int, array{
     *     key: string,
     *     name: string,
     *     official_name: string,
     *     level: OrganizationalLevel,
     *     acronym: string,
     *     palette: array<int, array{int, int, int}>,
     *     parent: ?string,
     *     sort_order: int,
     *     description: string
     * }>
     */
    private array $units = [
        [
            'key' => 'pimpinan',
            'name' => 'Direktur Sinkronisasi Urusan Pemerintahan Daerah I',
            'official_name' => 'Rahmat Hidayat',
            'level' => OrganizationalLevel::Pimpinan,
            'acronym' => 'DIR',
            'palette' => [[11, 40, 54], [38, 70, 80]],
            'parent' => null,
            'sort_order' => 1,
            'description' => 'Pimpinan tertinggi Direktorat Sinkronisasi Urusan Pemerintahan Daerah I. Data contoh.',
        ],
        [
            'key' => 'kabag-tu',
            'name' => 'Kepala Bagian Tata Usaha',
            'official_name' => 'Sinta Maharani',
            'level' => OrganizationalLevel::KepalaBagian,
            'acronym' => 'KABAG',
            'palette' => [[70, 28, 92], [122, 48, 137]],
            'parent' => 'pimpinan',
            'sort_order' => 10,
            'description' => 'Mengoordinasikan ketatausahaan, kepegawaian, keuangan, dan rumah tangga direktorat. Data contoh.',
        ],
        [
            'key' => 'kasubbag-tu',
            'name' => 'Kepala Subbagian Tata Usaha',
            'official_name' => 'Nadia Puspita',
            'level' => OrganizationalLevel::Kasubbag,
            'acronym' => 'TU',
            'palette' => [[126, 60, 31], [177, 82, 45]],
            'parent' => 'kabag-tu',
            'sort_order' => 10,
            'description' => 'Mengelola urusan tata usaha, surat-menyurat, arsip, dan layanan administrasi direktorat. Data contoh.',
        ],
        [
            'key' => 'kapokja-perencanaan',
            'name' => 'Kepala Kelompok Kerja Perencanaan dan Pelaporan',
            'official_name' => 'Dewi Lestari',
            'level' => OrganizationalLevel::Kapokja,
            'acronym' => 'REN',
            'palette' => [[116, 59, 26], [164, 91, 38]],
            'parent' => 'pimpinan',
            'sort_order' => 30,
            'description' => 'Menyiapkan perencanaan program, anggaran kerja, evaluasi, dan pelaporan kinerja direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-perencanaan-program',
            'name' => 'Ketua Tim Perencanaan Program',
            'official_name' => 'Andi Nugraha',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'RENPRO',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-perencanaan',
            'sort_order' => 10,
            'description' => 'Menyusun rencana program dan kegiatan tahunan direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-evaluasi-kinerja',
            'name' => 'Ketua Tim Evaluasi dan Pelaporan Kinerja',
            'official_name' => 'Maya Kartika',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'EVAL',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-perencanaan',
            'sort_order' => 20,
            'description' => 'Mengumpulkan data capaian dan menyusun laporan kinerja direktorat. Data contoh.',
        ],
        [
            'key' => 'kapokja-keuangan',
            'name' => 'Kepala Kelompok Kerja Keuangan dan Aset',
            'official_name' => 'Rizky Ramadhan',
            'level' => OrganizationalLevel::Kapokja,
            'acronym' => 'KEU',
            'palette' => [[116, 59, 26], [164, 91, 38]],
            'parent' => 'pimpinan',
            'sort_order' => 40,
            'description' => 'Mengelola anggaran, pembayaran, akuntansi, serta aset dan barang milik negara direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-anggaran',
            'name' => 'Ketua Tim Anggaran',
            'official_name' => 'Intan Permata',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'ANG',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-keuangan',
            'sort_order' => 10,
            'description' => 'Menyusun rencana anggaran dan memantau realisasi belanja direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-aset',
            'name' => 'Ketua Tim Aset dan Barang Milik Negara',
            'official_name' => 'Fajar Setiawan',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'BMN',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-keuangan',
            'sort_order' => 20,
            'description' => 'Menata administrasi aset dan barang milik negara di lingkungan direktorat. Data contoh.',
        ],
        [
            'key' => 'kapokja-umum',
            'name' => 'Kepala Kelompok Kerja Umum dan Kepegawaian',
            'official_name' => 'Bima Saputra',
            'level' => OrganizationalLevel::Kapokja,
            'acronym' => 'UMUM',
            'palette' => [[116, 59, 26], [164, 91, 38]],
            'parent' => 'pimpinan',
            'sort_order' => 50,
            'description' => 'Mengelola kepegawaian, rumah tangga, protokol, dan layanan umum direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-kepegawaian',
            'name' => 'Ketua Tim Kepegawaian',
            'official_name' => 'Ayu Wulandari',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'KEP',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-umum',
            'sort_order' => 10,
            'description' => 'Mengelola administrasi kepegawaian dan pengembangan sumber daya manusia direktorat. Data contoh.',
        ],
        [
            'key' => 'katim-rumah-tangga',
            'name' => 'Ketua Tim Rumah Tangga dan Protokol',
            'official_name' => 'Yoga Pratama',
            'level' => OrganizationalLevel::Katim,
            'acronym' => 'RT',
            'palette' => [[24, 81, 50], [41, 128, 75]],
            'parent' => 'kapokja-umum',
            'sort_order' => 20,
            'description' => 'Menangani urusan rumah tangga, perlengkapan, dan protokol kegiatan direktorat. Data contoh.',
        ],
        [
            'key' => 'fungsional-perencana',
            'name' => 'Jabatan Fungsional Perencana',
            'official_name' => 'Bagus Prakoso',
            'level' => OrganizationalLevel::Fungsional,
            'acronym' => 'FUNG',
            'palette' => [[120, 30, 85], [171, 55, 122]],
            'parent' => 'pimpinan',
            'sort_order' => 90,
            'description' => 'Melaksanakan tugas fungsional perencanaan pembangunan daerah. Data contoh tanpa foto.',
        ],
    ];

    public function run(): void
    {
        $this->removeLegacyUnits();

        /** @var array<string, int> $ids */
        $ids = [];

        foreach ($this->units as $data) {
            $unit = OrganizationalUnit::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'official_name' => $data['official_name'],
                    'level' => $data['level'],
                    'photo' => $this->photoPath($data),
                    'parent_id' => $data['parent'] !== null ? $ids[$data['parent']] : null,
                    'sort_order' => $data['sort_order'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'is_published' => true,
                ],
            );

            $ids[$data['key']] = (int) $unit->getKey();
        }
    }

    private function removeLegacyUnits(): void
    {
        OrganizationalUnit::query()
            ->whereIn('name', $this->legacyNames)
            ->get()
            ->each(fn (OrganizationalUnit $unit) => $unit->delete());
    }

    /**
     * @param  array{
     *     key: string,
     *     acronym: string,
     *     palette: array<int, array{int, int, int}>,
     *     level: OrganizationalLevel
     * }  $data
     */
    private function photoPath(array $data): ?string
    {
        if (! $data['level']->hasPhoto()) {
            return null;
        }

        $path = self::PHOTO_DIRECTORY.'/monogram-'.$data['key'].'.jpg';

        if (! $this->hasCurrentPhoto($path)) {
            $this->generatePhoto($data, $path);
        }

        return $path;
    }

    /**
     * Foto contoh hanya dibuat ulang jika belum ada atau masih memakai
     * dimensi lama yang membuat monogram terpotong pada kartu.
     */
    private function hasCurrentPhoto(string $path): bool
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return false;
        }

        $size = @getimagesize($disk->path($path));

        return $size !== false
            && $size[0] === self::PHOTO_WIDTH
            && $size[1] === self::PHOTO_HEIGHT;
    }

    /**
     * Generate foto monogram potret untuk sebuah jabatan. Ukuran mengikuti
     * proporsi kolom foto pada kartu agar huruf tidak terpotong.
     *
     * @param  array{acronym: string, palette: array<int, array{int, int, int}>}  $data
     */
    private function generatePhoto(array $data, string $path): void
    {
        $width = self::PHOTO_WIDTH;
        $height = self::PHOTO_HEIGHT;

        $image = imagecreatetruecolor($width, $height);

        imageantialias($image, true);

        [$from, $to] = $data['palette'];

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / ($height - 1);
            $color = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 88);
        imagefilledellipse($image, (int) ($width * 0.8), (int) ($height * 0.24), (int) ($width * 0.62), (int) ($width * 0.62), $overlay);

        $overlayDark = imagecolorallocatealpha($image, 0, 0, 0, 96);
        imagefilledellipse($image, (int) ($width * 0.22), (int) ($height * 0.88), (int) ($width * 0.7), (int) ($width * 0.7), $overlayDark);

        $textColor = imagecolorallocate($image, 255, 255, 255);
        $fontBold = $this->fontPath('arialbd.ttf') ?? $this->fontPath('seguibl.ttf');
        $maxTextWidth = (int) ($width * 0.78);

        if ($fontBold !== null) {
            $fontSize = 150;
            $box = imagettfbbox($fontSize, 0, $fontBold, $data['acronym']);
            $textWidth = abs($box[2] - $box[0]);

            if ($textWidth > $maxTextWidth) {
                $fontSize = max(24, (int) floor($fontSize * $maxTextWidth / $textWidth));
                $box = imagettfbbox($fontSize, 0, $fontBold, $data['acronym']);
                $textWidth = abs($box[2] - $box[0]);
            }

            $textHeight = abs($box[7] - $box[1]);

            imagettftext(
                $image,
                $fontSize,
                0,
                (int) (($width - $textWidth) / 2) - $box[0],
                (int) (($height + $textHeight) / 2),
                $textColor,
                $fontBold,
                $data['acronym'],
            );
        } else {
            imagestring($image, 5, (int) (($width - strlen($data['acronym']) * 9) / 2), (int) ($height / 2) - 7, $data['acronym'], $textColor);
        }

        $temp = tmpfile();

        if ($temp === false) {
            imagedestroy($image);

            throw new RuntimeException('Gagal membuat gambar sementara.');
        }

        $tempPath = stream_get_meta_data($temp)['uri'];

        imagejpeg($image, $tempPath, 90);

        Storage::disk('public')->put($path, (string) file_get_contents($tempPath));

        fclose($temp);
        imagedestroy($image);
    }

    private function fontPath(string $file): ?string
    {
        foreach (['C:\\Windows\\Fonts\\', '/usr/share/fonts/truetype/dejavu/', '/usr/share/fonts/truetype/liberation/'] as $directory) {
            $path = $directory.$file;

            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
