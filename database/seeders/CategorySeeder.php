<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Rubrik awal berdasarkan bidang kerja Direktorat SUPD I.
     *
     * Catatan: daftar unit dari pemilik proyek masih tentatif dan dapat
     * diubah administrator. Dua nama "Pertanian Pangan" dan "Pertanian dan
     * Pangan" digabung menjadi satu rubrik agar tidak duplikatif.
     *
     * @var array<int, array{name: string, description: string}>
     */
    private array $categories = [
        [
            'name' => 'Subbagian Tata Usaha',
            'description' => 'Informasi administrasi, kepegawaian, dan layanan internal direktorat.',
        ],
        [
            'name' => 'Subdirektorat Pertanian dan Pangan',
            'description' => 'Kegiatan sinkronisasi urusan pemerintahan daerah di bidang pertanian dan pangan.',
        ],
        [
            'name' => 'Subdirektorat Lingkungan Hidup',
            'description' => 'Kegiatan sinkronisasi urusan pemerintahan daerah di bidang lingkungan hidup.',
        ],
        [
            'name' => 'Subdirektorat Kehutanan',
            'description' => 'Kegiatan sinkronisasi urusan pemerintahan daerah di bidang kehutanan.',
        ],
        [
            'name' => 'Subdirektorat Pertanahan dan Penataan Ruang',
            'description' => 'Kegiatan sinkronisasi urusan pemerintahan daerah di bidang pertanahan dan penataan ruang.',
        ],
        [
            'name' => 'Substansi Energi Sumber Daya dan Mineral',
            'description' => 'Kegiatan sinkronisasi urusan pemerintahan daerah di bidang energi dan sumber daya mineral.',
        ],
        [
            'name' => 'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
            'description' => 'Kegiatan koordinasi kewilayahan dan perubahan iklim.',
        ],
    ];

    public function run(): void
    {
        foreach ($this->categories as $index => $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
