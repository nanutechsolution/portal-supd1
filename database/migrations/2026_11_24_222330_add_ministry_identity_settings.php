<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nilai default identitas instansi pada header publik.
     *
     * @var array<string, string>
     */
    private array $defaults = [
        'site_ministry_line1' => 'Direktorat Jenderal Bina Pembangunan Daerah',
        'site_ministry_line2' => 'Kementerian Dalam Negeri',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $rows = [];

        foreach ($this->defaults as $key => $value) {
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Jangan menimpa nilai yang sudah diubah administrator.
        DB::table('settings')->insertOrIgnore($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->defaults as $key => $value) {
            DB::table('settings')
                ->where('key', $key)
                ->where('value', $value)
                ->delete();
        }
    }
};
