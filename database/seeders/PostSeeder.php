<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Berita contoh beserta gambar utama.
 *
 * Setiap berita digenerate gambar 16:9-nya sendiri (GD) ke disk `public`,
 * jadi seeder tidak bergantung pada berkas eksternal atau jaringan.
 * Seeder bersifat idempotent: dijalankan ulang memperbarui konten tanpa
 * membuat duplikat (kunci update: judul).
 *
 * Konten bersifat contoh dan akan diganti/dilengkapi administrator.
 */
class PostSeeder extends Seeder
{
    /**
     * @var string
     */
    private const IMAGES_DIRECTORY = 'posts';

    /**
     * @var array<int, array{title: string, category: string, excerpt: string, content: string, palette: array{int, int, int}[], daysAgo: int, hour: int, featured: bool, breaking?: bool}>
     */
    private array $posts = [
        [
            'title' => 'Rakor Sinkronisasi Urusan Pertanian dan Pangan Semester I',
            'category' => 'Subdirektorat Pertanian dan Pangan',
            'excerpt' => 'Rapat koordinasi evaluasi target tanam dan penyaluran pupuk bersubsidi untuk skema program tahun berjalan.',
            'content' => '<p>Pada rapat koordinasi yang digelar di kantor direktorat, peserta mengevaluasi realisasi target tanam musim tanam berjalan dan penyerapan anggaran pupuk bersubsidi di sejumlah daerah prioritas.</p><p>Direktorat menyerukan agar pemerintah daerah memperkuat pendataan gabungan petani, penyuluh, dan kelurahan agar rekomendasi alokasi pupuk lebih tepat sasaran.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[13, 79, 63], [35, 118, 90]],
            'daysAgo' => 2,
            'hour' => 9,
            'featured' => true,
            'breaking' => true,
        ],
        [
            'title' => 'Monitoring dan Evaluasi Program Lingkungan Hidup di Daerah',
            'category' => 'Subdirektorat Lingkungan Hidup',
            'excerpt' => 'Tim direktorat melakukan monitoring langsung ke pelaksanaan pengelolaan sampah di kawasan pesisir Jawa Timur.',
            'content' => '<p>Monitoring lapangan dilaksanakan bersama dinas lingkungan hidup setempat untuk menilai capaian pengurangan timbunan sampah dan penerapan aturan pemisahan dari sumber.</p><p>Temuan lapangan dicatat untuk bahan rekomendasi koordinasi kebijakan pada koordinasi tingkat pusat-daerah.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[11, 78, 93], [20, 116, 139]],
            'daysAgo' => 6,
            'hour' => 10,
            'featured' => false,
        ],
        [
            'title' => 'Koordinasi Kebijakan Kehutanan dengan Pemerintah Daerah',
            'category' => 'Subdirektorat Kehutanan',
            'excerpt' => 'Kesepakatan awal mengenai skema perizinan pemanfaatan hasil hutan non-kayu bersama kementerian teknis.',
            'content' => '<p>Koordinasi ini membahas penyederhanaan perizinan pemanfaatan hasil hutan non-kayu secara transparan dengan tetap memperhatikan tata kelola kawasan hutan.</p><p>Distribusi rekomendasi teknis diharapkan mempercepat penerbitan izin usaha yang selama ini terhambat pada verifikasi lapangan.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[88, 58, 32], [141, 94, 57]],
            'daysAgo' => 12,
            'hour' => 8,
            'featured' => false,
        ],
        [
            'title' => 'Bimtek Pengawasan Pertanahan dan Penataan Ruang',
            'category' => 'Subdirektorat Pertanahan dan Penataan Ruang',
            'excerpt' => 'Bimbingan teknis bagi aparat daerah tentang pemanfaatan data pertanahan untuk penataan ruang terpadu.',
            'content' => '<p>Bimtek ini membahas integrasi peta fungsi ruang dengan data bidang tanah untuk mencegah tumpang tindih izin di wilayah berkembang.</p><p>Peserta dari 14 kabupaten/kota antusias memperdalam pemanfaatan Sistem Informasi Penataan Ruang yang tengah disempurnakan.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[87, 70, 23], [143, 116, 47]],
            'daysAgo' => 17,
            'hour' => 13,
            'featured' => false,
        ],
        [
            'title' => 'Rapat Konsultasi Energi dan Sumber Daya Mineral Terbarukan',
            'category' => 'Substansi Energi Sumber Daya dan Mineral',
            'excerpt' => 'Pembahasan insentif daerah untuk percepatan elektrifikasi berbasis energi terbarukan di desa-desa terpencil.',
            'content' => '<p>Konsultasi membahas skema insentif dan kemudahan perijinan pembangkit energi terbarukan skala kecil di daerah yang belum terjangkau jaringan PLN.</p><p>Direktorat mendorong kolaborasi pembiayaan antara pemerintah daerah, BUMDes, dan sektor swasta agar target elektrifikasi 2026 tercapai lebih cepat.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[62, 56, 24], [126, 109, 47]],
            'daysAgo' => 23,
            'hour' => 9,
            'featured' => false,
        ],
        [
            'title' => 'Diskusi Koordinasi Kewilayahan dan Rencana Aksi Perubahan Iklim',
            'category' => 'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
            'excerpt' => 'Penyusunan rencana aksi adaptasi perubahan iklim berbasis kearifan lokal bersama tokoh daerah.',
            'content' => '<p>Diskusi melibatkan tokoh masyarakat, perangkat daerah, dan akademisi untuk merumuskan aksi adaptasi perubahan iklim yang realistis bagi wilayah rawan kekeringan.</p><p>Kesimpulan diskusi akan ditindaklanjuti dengan penyusunan rencana aksi kewilayahan yang diselaraskan dengan rencana pembangunan daerah.</p><p><em>Contoh konten awal dari seeder — akan diganti atau dilengkapi administrator.</em></p>',
            'palette' => [[31, 63, 92], [52, 101, 140]],
            'daysAgo' => 30,
            'hour' => 10,
            'featured' => false,
        ],
    ];

    public function run(): void
    {
        $author = User::query()->orderBy('id')->first();

        if ($author === null) {
            throw new RuntimeException('UserSeeder harus dijalankan lebih dulu (tidak ada pengguna).');
        }

        $categories = Category::query()->pluck('id', 'name');

        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::query()->pluck('id', 'name');
        }

        foreach ($this->posts as $index => $data) {
            $categoryId = $categories->get($data['category']);

            if ($categoryId === null) {
                $this->command?->warn("Kategori tidak ditemukan, lewati: {$data['category']}");

                continue;
            }

            $imagePath = $this->generateImage($index, $data);

            Post::query()->updateOrCreate(
                ['title' => $data['title']],
                [
                    'category_id' => $categoryId,
                    'user_id' => $author->id,
                    'excerpt' => $data['excerpt'],
                    'content' => $data['content'],
                    'featured_image' => $imagePath,
                    'is_featured' => $data['featured'],
                    'is_breaking' => $data['breaking'] ?? false,
                    'status' => 'published',
                    'published_at' => now()->subDays($data['daysAgo'])->setTime($data['hour'], 0),
                ],
            );
        }
    }

    /**
     * Generate gambar utama 1600x900 (16:9) menggunakan GD dan simpan ke disk public.
     *
     * @param  array{title: string, category: string, palette: array{int, int, int}[], featured: bool}  $data
     */
    private function generateImage(int $index, array $data): string
    {
        $width = 1600;
        $height = 900;

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

        // Bentuk dekoratif translucent.
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 90);
        imagefilledellipse($image, 1420, 140, 520, 520, $overlay);

        $overlayDark = imagecolorallocatealpha($image, 0, 0, 0, 70);
        imagefilledellipse($image, 175, 780, 620, 620, $overlayDark);

        // Brand atas kiri.
        $brand = $this->allocateText($image, [255, 255, 255]);
        $fontBold = $this->fontPath('arialbd.ttf') ?? $this->fontPath('seguibl.ttf');
        $fontRegular = $this->fontPath('arial.ttf') ?? $fontBold;

        if ($fontBold !== null && $fontRegular !== null) {
            imagettftext($image, 44, 0, 72, 110, $brand, $fontBold, 'SUPD I');
            $this->drawRule($image, 72, 138, 148, $this->allocateText($image, [170, 210, 190]));
        } elseif ($brand) {
            imagestring($image, 5, 72, 78, 'SUPD I', $brand);
        }

        // Label kategori (chip) di atas judul.
        $category = mb_strtoupper($data['category']);
        $chipFontSize = 21;

        $chipBox = $fontRegular !== null ? $this->measureText($category, $chipFontSize, $fontRegular) : [0, 0];
        $chipWidth = $chipBox[0] + 56;
        $chipHeight = 44;
        $chipX = 72;
        $chipY = 375;

        imagefilledrectangle($image, $chipX, $chipY, $chipX + $chipWidth, $chipY + $chipHeight, imagecolorallocatealpha($image, 0, 0, 0, 60));
        $chipText = $this->allocateText($image, [235, 245, 240]);

        if ($fontRegular !== null) {
            imagettftext($image, $chipFontSize, 0, $chipX + 28, $chipY + 30, $chipText, $fontRegular, $category);
        } else {
            imagestring($image, 4, $chipX + 28, $chipY + 16, $category, $chipText);
        }

        // Judul berita, di-wrap dan rata tengah vertikal area teks.
        $titleFontSize = 54;
        $maxWidth = 1456;
        $maxLines = 3;

        if ($fontBold !== null) {
            $lines = $this->wrapText($data['title'], $titleFontSize, $fontBold, $maxWidth);
            $lines = array_slice($lines, 0, $maxLines);
        } else {
            $lines = array_slice($this->wrapTextByCount($data['title'], 40), 0, $maxLines);
        }

        $lineHeight = 72;
        $titleY = 500;

        foreach ($lines as $line) {
            if ($fontBold !== null) {
                $lineBox = $this->measureText($line, $titleFontSize, $fontBold);
                $x = (int) (($width - $lineBox[0]) / 2);

                imagettftext($image, $titleFontSize, 0, $x, $titleY, $brand, $fontBold, $line);
            } else {
                imagestring($image, 5, 72, $titleY - 30, $line, $brand);
            }

            $titleY += $lineHeight;
        }

        // Balok bawah.
        imagefilledrectangle($image, 0, $height - 92, $width, $height, imagecolorallocatealpha($image, 0, 0, 0, 45));

        $footerLeft = 'Direktorat Sinkronisasi Urusan Pemerintahan Daerah I';

        $footerText = $this->allocateText($image, [214, 226, 220]);

        if ($fontRegular !== null) {
            imagettftext($image, 17, 0, 72, $height - 34, $footerText, $fontRegular, $footerLeft);

            $note = 'Contoh konten';
            $noteBox = $this->measureText($note, 17, $fontRegular);
            imagettftext($image, 17, 0, $width - 72 - $noteBox[0], $height - 34, $footerText, $fontRegular, $note);
        }

        $suffix = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $path = self::IMAGES_DIRECTORY.'/seeder-berita-'.$suffix.'.jpg';

        $temp = tmpfile();

        if ($temp === false) {
            imagedestroy($image);

            throw new RuntimeException('Gagal membuat gambar sementara.');
        }

        $tempPath = stream_get_meta_data($temp)['uri'];

        imagejpeg($image, $tempPath, 88);

        Storage::disk('public')->put($path, (string) file_get_contents($tempPath));

        fclose($temp);
        imagedestroy($image);

        return $path;
    }

    /**
     * @param  \GdImage  $image
     * @param  array{int, int, int}  $rgb
     */
    private function allocateText($image, array $rgb): int
    {
        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * @param  \GdImage  $image
     */
    private function drawRule($image, int $x1, int $y, int $x2, int $color): void
    {
        imageline($image, $x1, $y, $x2, $y, $color);
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

    /**
     * @return array{int, int} [lebar, tinggi] dalam piksel
     */
    private function measureText(string $text, float $fontSize, string $font): array
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);

        return [abs($box[2] - $box[0]), abs($box[7] - $box[1])];
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, float $fontSize, string $font, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = trim($current.' '.$word);
            [$width] = $this->measureText($candidate, $fontSize, $font);

            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;

                continue;
            }

            $current = $candidate;
        }

        $lines[] = $current;

        return $lines;
    }

    /**
     * Fallback tanpa TTF: wrap berdasarkan jumlah karakter.
     *
     * @return array<int, string>
     */
    private function wrapTextByCount(string $text, int $maxChars): array
    {
        return explode("\n", wordwrap($text, $maxChars, "\n", true));
    }
}
