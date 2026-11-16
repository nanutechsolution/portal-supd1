<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Halaman profil awal. Isi masih berupa placeholder dan wajib
     * dilengkapi administrator melalui panel.
     *
     * @var array<int, array{slug: string, title: string, content: string, meta_description: string}>
     */
    private array $pages = [
        [
            'slug' => 'tentang-supd1',
            'title' => 'Tentang SUPD I',
            'content' => '<p>Direktorat Sinkronisasi Urusan Pemerintahan Daerah I merupakan unit kerja yang menjalankan fungsi sinkronisasi urusan pemerintahan daerah pada bidang-bidang yang menjadi kewenangannya.</p><p><em>Uraian resmi mengenai profil, sejarah, dan struktur organisasi belum diisi. Administrator dapat melengkapi bagian ini melalui menu Halaman Profil di panel admin.</em></p>',
            'meta_description' => 'Profil Direktorat Sinkronisasi Urusan Pemerintahan Daerah I.',
        ],
        [
            'slug' => 'tugas-dan-fungsi',
            'title' => 'Tugas dan Fungsi',
            'content' => '<p>Direktorat SUPD I melaksanakan tugas di bidang sinkronisasi urusan pemerintahan daerah sesuai dengan ketentuan peraturan perundang-undangan.</p><p><em>Rincian tugas dan fungsi resmi belum diisi. Administrator dapat melengkapi bagian ini melalui menu Halaman Profil di panel admin.</em></p>',
            'meta_description' => 'Tugas dan fungsi Direktorat Sinkronisasi Urusan Pemerintahan Daerah I.',
        ],
    ];

    public function run(): void
    {
        foreach ($this->pages as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                $page,
            );
        }
    }
}
