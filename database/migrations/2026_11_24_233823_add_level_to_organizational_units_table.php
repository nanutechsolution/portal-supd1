<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizational_units', function (Blueprint $table) {
            $table->string('level', 32)
                ->nullable()
                ->after('official_name')
                ->index();
        });

        $this->backfillLevels();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizational_units', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropColumn('level');
        });
    }

    /**
     * Isi level untuk baris lama berdasarkan nama dan posisi akar pohon.
     */
    private function backfillLevels(): void
    {
        $keywords = [
            'fungsional' => ['fungsional'],
            'katim' => ['katim', 'ketua tim'],
            'kapokja' => ['kapokja', 'kelompok kerja'],
            'kasubbag' => ['subbagian', 'sub bagian'],
            'kepala_bagian' => ['kepala bagian', 'subdirektorat', 'substansi'],
        ];

        foreach ($keywords as $level => $needles) {
            DB::table('organizational_units')
                ->whereNull('level')
                ->where(function ($query) use ($needles): void {
                    foreach ($needles as $needle) {
                        $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.$needle.'%']);
                    }
                })
                ->update(['level' => $level]);
        }

        DB::table('organizational_units')
            ->whereNull('level')
            ->whereNull('parent_id')
            ->update(['level' => 'pimpinan']);
    }
};
