<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Konsistensi ekspor CSV saat data berubah konkuren.
 *
 * Simulasi konkurensi dilakukan dengan DB::listen / DB::beforeExecuting yang
 * menyisipkan atau menghapus baris pada titik eksekusi query yang tepat —
 * BUKAN dengan mengeksekusi dua query berurutan. Test berjalan di SQLite
 * in-memory (phpunit.xml), bukan MySQL/InnoDB — lihat laporan untuk batas
 * isolation semantics.
 */
class AuditLogExportConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Setting::flushCache();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_normal_export_reports_equal_snapshot_and_emitted_rows(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::factory()->count(3)->create(['module' => 'posts']);
        AuditLog::factory()->create(['module' => 'users']);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));

        $this->assertSame(3, $exported->after['rows_snapshot']);
        $this->assertSame(3, $exported->after['rows_emitted']);
        $this->assertCount(3, $rows);
    }

    public function test_dataset_larger_than_one_chunk(): void
    {
        $admin = User::factory()->admin()->create();

        $this->seedRows(1100);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));

        $this->assertSame(1100, $exported->after['rows_snapshot']);
        $this->assertSame(1100, $exported->after['rows_emitted']);
        $this->assertCount(1100, $rows);

        $this->assertUniqueLabels($rows, 1100);
    }

    public function test_insert_after_snapshot_is_never_exported(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::factory()->create(['module' => 'posts', 'subject_label' => 'Asli Satu']);
        AuditLog::factory()->create(['module' => 'posts', 'subject_label' => 'Asli Dua']);

        $inserted = (object) ['done' => false];

        DB::connection()->listen(function ($query) use ($inserted): void {
            if ($inserted->done) {
                return;
            }

            if (! str_contains($query->sql, 'rows_snapshot')) {
                return;
            }

            $inserted->done = true;

            // Terjadi SETELAH query snapshot dieksekusi: baris ini merepresentasikan
            // aktivitas lain yang berkomit di tengah ekspor.
            DB::table('audit_logs')->insert([
                'actor_name' => 'Sisipan Bersamaan',
                'event' => 'created',
                'module' => 'posts',
                'subject_label' => 'Sisipan Setelah Snapshot',
                'status' => 'success',
                'created_at' => '2026-01-01 00:00:00',
            ]);
        });

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));
        $labels = array_column($rows, 4);

        $this->assertSame(2, $exported->after['rows_snapshot']);
        $this->assertSame(2, $exported->after['rows_emitted']);
        $this->assertCount(2, $rows);
        $this->assertContains('Asli Satu', $labels);
        $this->assertContains('Asli Dua', $labels);
        $this->assertNotContains('Sisipan Setelah Snapshot', $labels);
    }

    public function test_deleting_already_read_rows_during_streaming_does_not_skip_anything(): void
    {
        $admin = User::factory()->admin()->create();

        $this->seedRows(1100);

        $state = (object) ['done' => false];

        // Setelah query chunk PERTAMA (belum ada klausa cursor) selesai,
        // hapus 5 baris yang SUDAH terbaca pada chunk tersebut.
        DB::connection()->listen(function ($query) use ($state): void {
            if ($state->done) {
                return;
            }

            if (! str_contains($query->sql, 'audit_logs') || ! str_contains($query->sql, 'limit 500')) {
                return;
            }

            if (str_contains($query->sql, 'created_at" <')) {
                return;
            }

            $state->done = true;

            // Ids sebenarnya tidak sama dengan nomor label karena audit
            // user-creation memakai id 1 — cari id berdasarkan label.
            $ids = DB::table('audit_logs')
                ->whereIn('subject_label', ['Row 1096', 'Row 1097', 'Row 1098', 'Row 1099', 'Row 1100'])
                ->pluck('id');

            DB::table('audit_logs')->whereIn('id', $ids)->delete();
        });

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));
        $labels = array_column($rows, 4);

        $this->assertSame(1100, $exported->after['rows_snapshot']);
        $this->assertSame(1100, $exported->after['rows_emitted']);
        $this->assertCount(1100, $rows);

        // Baris yang dihapus PASCA-baca tetap ter-ekspor (sudah ditulis).
        foreach ([1096, 1097, 1098, 1099, 1100] as $id) {
            $this->assertContains("Row {$id}", $labels);
        }

        $this->assertUniqueLabels($rows, 1100);
    }

    public function test_deleting_unread_rows_during_streaming_drops_them_without_skipping_others(): void
    {
        $admin = User::factory()->admin()->create();

        $this->seedRows(1100);

        $state = (object) ['done' => false];

        // Setelah chunk pertama ter-baca (ids 1100..601), hapus 5 baris yang
        // BELUM dibaca (ids 600..596) — simulasi retention pruning di tengah ekspor.
        DB::connection()->listen(function ($query) use ($state): void {
            if ($state->done) {
                return;
            }

            if (! str_contains($query->sql, 'audit_logs') || ! str_contains($query->sql, 'limit 500')) {
                return;
            }

            if (str_contains($query->sql, 'created_at" <')) {
                return;
            }

            $state->done = true;

            // Ids sebenarnya tidak sama dengan nomor label karena audit
            // user-creation memakai id 1 — hapus berdasarkan label.
            $ids = DB::table('audit_logs')
                ->whereIn('subject_label', ['Row 596', 'Row 597', 'Row 598', 'Row 599', 'Row 600'])
                ->pluck('id');

            DB::table('audit_logs')->whereIn('id', $ids)->delete();
        });

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));
        $labels = array_column($rows, 4);

        $this->assertSame(1100, $exported->after['rows_snapshot']);
        $this->assertSame(1095, $exported->after['rows_emitted']);
        $this->assertCount(1095, $rows);

        foreach ([596, 597, 598, 599, 600] as $id) {
            $this->assertNotContains("Row {$id}", $labels);
        }

        // Tidak ada baris SEKITAR yang ikut terlewat (keyset, bukan offset).
        $this->assertContains('Row 601', $labels);
        $this->assertContains('Row 595', $labels);
        $this->assertUniqueLabels($rows, 1095);
    }

    public function test_row_order_is_created_at_desc_then_id_desc(): void
    {
        $admin = User::factory()->admin()->create();

        $times = [
            ['2026-01-03 10:00:00', 'Baru'],
            ['2026-01-01 08:00:00', 'Terlama'],
            ['2026-01-02 09:00:00', 'Tengah'],
        ];

        foreach ($times as [$time, $label]) {
            AuditLog::factory()->create([
                'module' => 'posts',
                'subject_label' => $label,
                'created_at' => $time,
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));
        $timesOut = array_column($rows, 0);

        $this->assertSame(['2026-01-03 10:00:00', '2026-01-02 09:00:00', '2026-01-01 08:00:00'], $timesOut);
    }

    public function test_export_respects_table_filters_and_excludes_itself(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::factory()->create(['module' => 'posts', 'event' => 'created', 'subject_label' => 'Target Satu']);
        AuditLog::factory()->create(['module' => 'posts', 'event' => 'deleted', 'subject_label' => 'Target Dua']);
        AuditLog::factory()->create(['module' => 'users', 'event' => 'created', 'subject_label' => 'Bukan Target']);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->firstOrFail();
        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));
        $labels = array_column($rows, 4);
        $activities = array_column($rows, 2);

        $this->assertSame(2, $exported->after['rows_snapshot']);
        $this->assertSame(2, $exported->after['rows_emitted']);
        $this->assertContains('Target Satu', $labels);
        $this->assertContains('Target Dua', $labels);
        $this->assertNotContains('Bukan Target', $labels);

        // Event ekspor itu sendiri tidak boleh muncul di dalam file ekspor.
        $this->assertNotContains('Audit log diekspor', $activities);
    }

    public function test_exported_file_has_no_duplicate_rows_across_chunks(): void
    {
        $admin = User::factory()->admin()->create();

        // created_at identik memaksa cursor bergantung pada id.
        $this->seedRows(1100);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $rows = $this->parseCsv((string) base64_decode((string) $component->effects['download']['content']));

        $this->assertUniqueLabels($rows, count($rows));
    }

    private function seedRows(int $count): void
    {
        for ($id = 1; $id <= $count; $id += 50) {
            $batch = [];

            for ($n = 0; $n < 50 && ($id + $n) <= $count; $n++) {
                $rowId = $id + $n;
                $batch[] = [
                    'actor_name' => 'Sistem',
                    'event' => 'created',
                    'module' => 'posts',
                    'subject_label' => 'Row '.$rowId,
                    'status' => 'success',
                    'created_at' => '2026-01-01 00:00:00',
                ];
            }

            DB::table('audit_logs')->insert($batch);
        }
    }

    /**
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function assertUniqueLabels(array $rows, int $expected): void
    {
        $labels = array_column($rows, 4);

        $this->assertCount($expected, array_unique($labels), 'Tidak boleh ada baris duplikat.');
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function parseCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        $handle = fopen('php://temp', 'r+');

        $this->assertIsResource($handle);

        fwrite($handle, $content);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return array_slice($rows, 1);
    }
}
