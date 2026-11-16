<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    /**
     * Reference units supplied by the stakeholder.
     *
     * These are placeholders only. No reporting line, official name, or
     * publication status is assumed: every unit is seeded as a root and kept
     * unpublished until an administrator confirms the real structure.
     *
     * @var array<int, string>
     */
    private array $units = [
        'Subbagian Tata Usaha',
        'Subdirektorat Pertanian Pangan',
        'Subdirektorat Lingkungan Hidup',
        'Subdirektorat Kehutanan',
        'Subdirektorat Pertanahan dan Penataan Ruang',
        'Subdirektorat Pertanian dan Pangan',
        'Substansi Energi Sumber Daya dan Mineral',
        'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
    ];

    public function run(): void
    {
        foreach ($this->units as $index => $name) {
            OrganizationalUnit::query()->firstOrCreate(
                ['name' => $name],
                [
                    'official_name' => null,
                    'photo' => null,
                    'parent_id' => null,
                    'sort_order' => ($index + 1) * 10,
                    'description' => null,
                    'is_active' => true,
                    'is_published' => false,
                ],
            );
        }
    }
}
