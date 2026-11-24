<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class WebsiteSettingsSeeder extends Seeder
{
    /**
     * Isi nilai awal identitas instansi pada header publik.
     *
     * Menggunakan firstOrCreate agar konfigurasi yang sudah diubah
     * administrator tidak tertimpa saat seeder dijalankan ulang.
     */
    public function run(): void
    {
        foreach (['site_ministry_line1', 'site_ministry_line2'] as $key) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => (string) config("portal.settings.{$key}")],
            );
        }

        Setting::flushCache();
    }
}
