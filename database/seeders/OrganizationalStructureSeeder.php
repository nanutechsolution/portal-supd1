<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Struktur organisasi contoh beserta foto (avatar monogram) untuk setiap
 * jabatan.
 *
 * Hierarki: Direktur (root) → 7 unit langsung di bawahnya (Subbagian,
 * 6 Subdirektorat/Substansi). Gambar dibuat dengan GD dan disimpan ke disk
 * `public` pada direktori `structure/` — tidak bergantung berkas eksternal.
 *
 * Seluruh nama pejabat bersifat contoh dan identitas personel TIDAK
 * di-generate agar tidak disalahartikan sebagai data nyata; hanya jabatan
 * yang diisi. Seeder bersifat idempotent (kunci update: nama unit).
 */
class OrganizationalStructureSeeder extends Seeder
{
    /**
     * @var string
     */
    private const PHOTO_DIRECTORY = 'structure';

    /**
     * @var array<int, array{key: string, name: string, official_name: string, acronym: string, palette: array{int, int, int}[], parent: ?string, sort_order: int, description: string}>
     */
    private array $units = [
        [
            'key' => 'direktur',
            'name' => 'Direktur',
            'official_name' => 'Direktur Sinkronisasi Urusan Pemerintahan Daerah I',
            'acronym' => 'DIR',
            'palette' => [[11, 40, 54], [38, 70, 80]],
            'parent' => null,
            'sort_order' => 1,
            'description' => 'Contoh bagan struktur organisasi dari seeder; hubungan atasan–bawahan dan pengisian pejabat dilengkapi administrator.',
        ],
        [
            'key' => 'tata-usaha',
            'name' => 'Subbagian Tata Usaha',
            'official_name' => 'Kepala Subbagian Tata Usaha',
            'acronym' => 'TU',
            'palette' => [[38, 70, 80], [70, 111, 128]],
            'parent' => 'direktur',
            'sort_order' => 10,
            'description' => 'Urusan ketatausahaan, kepegawaian, keuangan, dan kepegawaian direktorat. Contoh struktur.',
        ],
        [
            'key' => 'pertanian-pangan',
            'name' => 'Subdirektorat Pertanian dan Pangan',
            'official_name' => 'Kepala Subdirektorat Pertanian dan Pangan',
            'acronym' => 'SPP',
            'palette' => [[15, 73, 50], [35, 118, 90]],
            'parent' => 'direktur',
            'sort_order' => 20,
            'description' => 'Sinkronisasi urusan pemerintahan daerah di bidang pertanian dan pangan. Contoh struktur.',
        ],
        [
            'key' => 'lingkungan-hidup',
            'name' => 'Subdirektorat Lingkungan Hidup',
            'official_name' => 'Kepala Subdirektorat Lingkungan Hidup',
            'acronym' => 'SLH',
            'palette' => [[11, 78, 93], [20, 116, 139]],
            'parent' => 'direktur',
            'sort_order' => 30,
            'description' => 'Sinkronisasi urusan pemerintahan daerah di bidang lingkungan hidup. Contoh struktur.',
        ],
        [
            'key' => 'kehutanan',
            'name' => 'Subdirektorat Kehutanan',
            'official_name' => 'Kepala Subdirektorat Kehutanan',
            'acronym' => 'SKH',
            'palette' => [[88, 58, 32], [141, 94, 57]],
            'parent' => 'direktur',
            'sort_order' => 40,
            'description' => 'Sinkronisasi urusan pemerintahan daerah di bidang kehutanan. Contoh struktur.',
        ],
        [
            'key' => 'pertanahan',
            'name' => 'Subdirektorat Pertanahan dan Penataan Ruang',
            'official_name' => 'Kepala Subdirektorat Pertanahan dan Penataan Ruang',
            'acronym' => 'SPR',
            'palette' => [[87, 70, 23], [143, 116, 47]],
            'parent' => 'direktur',
            'sort_order' => 50,
            'description' => 'Sinkronisasi urusan pemerintahan daerah di bidang pertanahan dan penataan ruang. Contoh struktur.',
        ],
        [
            'key' => 'energi',
            'name' => 'Substansi Energi Sumber Daya dan Mineral',
            'official_name' => 'Kepala Substansi Energi Sumber Daya dan Mineral',
            'acronym' => 'ESM',
            'palette' => [[62, 56, 24], [126, 109, 47]],
            'parent' => 'direktur',
            'sort_order' => 60,
            'description' => 'Sinkronisasi urusan pemerintahan daerah di bidang energi dan sumber daya mineral. Contoh struktur.',
        ],
        [
            'key' => 'kewilayahan',
            'name' => 'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
            'official_name' => 'Kepala Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
            'acronym' => 'KKPI',
            'palette' => [[31, 63, 92], [52, 101, 140]],
            'parent' => 'direktur',
            'sort_order' => 70,
            'description' => 'Koordinasi kewilayahan dan perubahan iklim. Contoh struktur.',
        ],
    ];

    public function run(): void
    {
        /** @var array<string, int> $ids */
        $ids = [];

        foreach ($this->units as $data) {
            $photoPath = $this->generatePhoto($data);

            $unit = OrganizationalUnit::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'official_name' => $data['official_name'],
                    'photo' => $photoPath,
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

    /**
     * Generate foto monogram 640x640 untuk sebuah jabatan.
     *
     * @param  array{acronym: string, palette: array{int, int, int}[]}  $data
     */
    private function generatePhoto(array $data): string
    {
        $size = 640;

        $image = imagecreatetruecolor($size, $size);

        imageantialias($image, true);

        [$from, $to] = $data['palette'];

        for ($y = 0; $y < $size; $y++) {
            $ratio = $y / ($size - 1);
            $color = imagecolorallocate(
                $image,
                (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) round($from[2] + ($to[2] - $from[2]) * $ratio),
            );
            imageline($image, 0, $y, $size, $y, $color);
        }

        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 88);
        imagefilledellipse($image, 505, 140, 380, 380, $overlay);

        $overlayDark = imagecolorallocatealpha($image, 0, 0, 0, 96);
        imagefilledellipse($image, 150, 545, 420, 420, $overlayDark);

        $textColor = imagecolorallocate($image, 255, 255, 255);
        $fontBold = $this->fontPath('arialbd.ttf') ?? $this->fontPath('seguibl.ttf');

        if ($fontBold !== null) {
            $fontSize = 210;
            $box = imagettfbbox($fontSize, 0, $fontBold, $data['acronym']);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);

            imagettftext(
                $image,
                $fontSize,
                0,
                (int) (($size - $textWidth) / 2) - $box[0],
                (int) (($size + $textHeight) / 2),
                $textColor,
                $fontBold,
                $data['acronym'],
            );
        } else {
            imagestring($image, 5, (int) (($size - strlen($data['acronym']) * 9) / 2), (int) ($size / 2) - 7, $data['acronym'], $textColor);
        }

        $path = self::PHOTO_DIRECTORY.'/monogram-'.strtolower($data['acronym']).'.jpg';

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

        return $path;
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
