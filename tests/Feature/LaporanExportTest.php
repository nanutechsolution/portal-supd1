<?php

namespace Tests\Feature;

use App\Filament\Pages\Laporan;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\ReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class LaporanExportTest extends TestCase
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

    private function seedPost(string $title, array $attributes = []): Post
    {
        return Post::factory()->published()->create(array_merge(['title' => $title], $attributes));
    }

    private function writeCsv(array $input): array
    {
        $path = tempnam(sys_get_temp_dir(), 'laporan-test-').'.csv';
        ReportExporter::write($input, $path);

        try {
            $content = (string) file_get_contents($path);
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $content);
            rewind($handle);

            $rows = [];
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            return $rows;
        } finally {
            @unlink($path);
        }
    }

    private function writeXlsx(array $input): array
    {
        $path = tempnam(sys_get_temp_dir(), 'laporan-test-').'.xlsx';
        ReportExporter::write($input, $path);

        try {
            $reader = new Reader;
            $reader->open($path);

            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
            $reader->close();

            return $rows;
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function flatText(array $rows): string
    {
        return implode("\n", array_map(
            fn (array $row): string => implode('|', array_map(fn (mixed $cell): string => (string) $cell, $row)),
            $rows,
        ));
    }

    private function countRowsStartingWith(array $rows, string $prefix): int
    {
        $count = 0;

        foreach ($rows as $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && str_starts_with($cell, $prefix)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function test_admin_can_open_laporan_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Filter Laporan')
            ->assertSee('Jenis Laporan');
    }

    public function test_editor_cannot_open_laporan_page(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/laporan')->assertForbidden();
    }

    public function test_berita_export_csv_respects_date_category_and_status_filters(): void
    {
        $kategoriA = Category::factory()->create(['name' => 'Kategori Filter Satu']);
        $kategoriB = Category::factory()->create(['name' => 'Kategori Filter Dua']);

        $inA = $this->seedPost('Berita Filter Masuk A', ['category_id' => $kategoriA->id, 'published_at' => '2026-11-05 09:00:00']);
        $inA2 = $this->seedPost('Berita Filter Masuk A2', ['category_id' => $kategoriA->id, 'published_at' => '2026-11-06 09:00:00']);
        $inB = $this->seedPost('Berita Filter Masuk B', ['category_id' => $kategoriB->id, 'published_at' => '2026-11-05 09:00:00']);
        $draftA = Post::factory()->create(['title' => 'Berita Filter Draf A', 'category_id' => $kategoriA->id, 'published_at' => '2026-11-05 09:00:00']);
        $outside = $this->seedPost('Berita Filter Luar Periode', ['category_id' => $kategoriA->id, 'published_at' => '2026-09-01 09:00:00']);

        $rows = $this->writeCsv([
            'jenis' => 'berita',
            'format' => 'csv',
            'status' => 'terbit',
            'dateFrom' => '2026-11-01',
            'dateUntil' => '2026-11-30',
            'category_id' => $kategoriA->id,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Berita Filter Masuk A', $text);
        $this->assertStringContainsString('Berita Filter Masuk A2', $text);
        $this->assertStringNotContainsString('Berita Filter Masuk B', $text);
        $this->assertStringNotContainsString('Berita Filter Draf A', $text);
        $this->assertStringNotContainsString('Berita Filter Luar Periode', $text);
        $this->assertStringContainsString('Judul', $text);
    }

    public function test_berita_export_xlsx_respects_filters_and_is_readable(): void
    {
        $kategori = Category::factory()->create(['name' => 'Kategori XLSX']);

        $this->seedPost('Berita XLSX Satu', ['category_id' => $kategori->id]);
        $this->seedPost('Berita XLSX Dua', ['category_id' => $kategori->id]);

        $rows = $this->writeXlsx([
            'jenis' => 'berita',
            'format' => 'xlsx',
            'status' => 'terbit',
            'dateFrom' => null,
            'dateUntil' => null,
            'category_id' => $kategori->id,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Berita XLSX Satu', $text);
        $this->assertStringContainsString('Berita XLSX Dua', $text);
        $this->assertStringContainsString('Kategori XLSX', $text);
        $this->assertStringContainsString('Judul', $text);
    }

    public function test_export_does_not_change_views(): void
    {
        $postA = $this->seedPost('Berita Views A', ['views' => 123]);
        $postB = $this->seedPost('Berita Views B', ['views' => 77]);

        $viewsBefore = Post::query()->sum('views');

        foreach (['berita', 'publikasi', 'kunjungan', 'terpopuler', 'kategori'] as $jenis) {
            $path = tempnam(sys_get_temp_dir(), 'laporan-test-').'.csv';
            ReportExporter::write(compact('jenis') + ['format' => 'csv', 'status' => 'semua', 'dateFrom' => null, 'dateUntil' => null, 'category_id' => null], $path);
            @unlink($path);
        }

        $this->assertSame($viewsBefore, Post::query()->sum('views'));
        $this->assertSame(123, $postA->refresh()->views);
        $this->assertSame(77, $postB->refresh()->views);
    }

    public function test_formula_injection_is_neutralized_in_csv_and_xlsx(): void
    {
        Post::factory()->published()->create(['title' => '=HYPERLINK("http://evil.test","klik")']);
        Post::factory()->published()->create(['title' => '@SUM(1+1)']);
        Post::factory()->published()->create(['title' => '  -2+3']);
        Post::factory()->published()->create(['title' => '+SUM(A1)']);

        foreach (['csv', 'xlsx'] as $format) {
            $rows = $format === 'csv' ? $this->writeCsv(['jenis' => 'berita', 'format' => 'csv']) : $this->writeXlsx(['jenis' => 'berita', 'format' => 'xlsx']);

            foreach ($rows as $row) {
                foreach ($row as $cell) {
                    $text = (string) $cell;

                    if ($text === '') {
                        continue;
                    }

                    $significant = ltrim($text, " \t\r\n");
                    $firstTrigger = $significant[0] ?? '';

                    if (in_array($firstTrigger, ['=', '+', '-', '@'], true)) {
                        $this->assertSame("'", $text[0], 'Sel yang diawali trigger formula harus dinetralkan dengan apostrof.');
                    }
                }
            }

            $text = $this->flatText($rows);
            $this->assertStringContainsString("'=HYPERLINK", $text);
            $this->assertStringContainsString("'@SUM", $text);
        }
    }

    public function test_export_handles_empty_dataset(): void
    {
        $rows = $this->writeCsv(['jenis' => 'berita', 'format' => 'csv']);
        $text = $this->flatText($rows);

        $this->assertStringContainsString('LAPORAN', $text);
        $this->assertStringContainsString('Judul', $text);
        $this->assertSame(0, $this->countRowsStartingWith($rows, 'Berita'));
    }

    public function test_large_dataset_exports_all_rows(): void
    {
        Post::factory()->published()->count(600)->sequence(
            fn ($sequence) => ['title' => 'Berkas Besar '.$sequence->index],
        )->create();

        $rows = $this->writeCsv(['jenis' => 'berita', 'format' => 'csv']);

        $this->assertSame(600, $this->countRowsStartingWith($rows, 'Berkas Besar'));
    }

    public function test_publikasi_report_contains_summary_numbers(): void
    {
        Post::factory()->published()->count(3)->create(['published_at' => '2026-11-05 09:00:00', 'views' => 10]);
        Post::factory()->count(2)->create(); // draf
        Post::factory()->published()->create(['published_at' => now()->addDays(3), 'title' => 'Terjadwal Publikasi']);

        $rows = $this->writeCsv([
            'jenis' => 'publikasi',
            'format' => 'csv',
            'status' => 'terbit',
            'dateFrom' => '2026-11-01',
            'dateUntil' => '2026-11-30',
            'category_id' => null,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('RINGKASAN', $text);
        $this->assertStringContainsString('Berita terbit (periode)|3', $text);
        $this->assertStringContainsString('Draf (keseluruhan)|2', $text);
        $this->assertStringContainsString('Terjadwal (menunggu publikasi)|1', $text);
        $this->assertStringContainsString('BULANAN / HARIAN', $text);
    }

    public function test_kunjungan_report_flags_period_before_rollup(): void
    {
        $post = Post::factory()->published()->create(['views' => 42]);
        DB::table('post_view_statistics')->insert([
            ['post_id' => $post->id, 'view_date' => '2026-11-10', 'views' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Periode sebelum rollup tersedia: 1–31 Oktober 2026.
        $rows = $this->writeCsv([
            'jenis' => 'kunjungan',
            'format' => 'csv',
            'status' => 'semua',
            'dateFrom' => '2026-10-01',
            'dateUntil' => '2026-10-31',
            'category_id' => null,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Data kunjungan per hari/bulan tersedia sejak', $text);
        $this->assertStringContainsString('posts.views', $text);
        $this->assertStringContainsString('Kumulatif', $text);
    }

    public function test_kunjungan_report_includes_rollup_totals_and_cumulative(): void
    {
        $post = Post::factory()->published()->create(['views' => 100]);
        DB::table('post_view_statistics')->insert([
            ['post_id' => $post->id, 'view_date' => '2026-11-14', 'views' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['post_id' => $post->id, 'view_date' => '2026-11-15', 'views' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $rows = $this->writeCsv([
            'jenis' => 'kunjungan',
            'format' => 'csv',
            'status' => 'semua',
            'dateFrom' => '2026-11-14',
            'dateUntil' => '2026-11-15',
            'category_id' => null,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('8', $text);
        $this->assertStringContainsString('Kumulatif', $text);
        $this->assertStringContainsString('TOTAL', $text);
    }

    public function test_kategori_report_lists_categories_and_total_row(): void
    {
        $kategoriA = Category::factory()->create(['name' => 'Kategori Laporan A']);
        $kategoriB = Category::factory()->create(['name' => 'Kategori Laporan B']);

        Post::factory()->published()->count(2)->create(['category_id' => $kategoriA->id, 'views' => 10, 'published_at' => '2026-11-05 09:00:00']);
        Post::factory()->published()->create(['category_id' => $kategoriB->id, 'views' => 30, 'published_at' => '2026-11-06 09:00:00']);

        $rows = $this->writeCsv([
            'jenis' => 'kategori',
            'format' => 'csv',
            'status' => 'terbit',
            'dateFrom' => '2026-11-01',
            'dateUntil' => '2026-11-30',
            'category_id' => null,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Kategori Laporan A', $text);
        $this->assertStringContainsString('Kategori Laporan B', $text);
        $this->assertStringContainsString('TOTAL', $text);
        $this->assertStringContainsString('Share', $text);
    }

    public function test_terpopuler_report_orders_by_views_and_respects_category(): void
    {
        $kategoriA = Category::factory()->create(['name' => 'Kategori Populer A']);
        $kategoriB = Category::factory()->create(['name' => 'Kategori Populer B']);

        $this->seedPost('Populer Rendah', ['category_id' => $kategoriA->id, 'views' => 10]);
        $this->seedPost('Populer Tertinggi', ['category_id' => $kategoriA->id, 'views' => 500]);
        $this->seedPost('Populer Sedang', ['category_id' => $kategoriA->id, 'views' => 100]);
        $this->seedPost('Populer Kategori B', ['category_id' => $kategoriB->id, 'views' => 9999]);

        $rows = $this->writeCsv([
            'jenis' => 'terpopuler',
            'format' => 'csv',
            'status' => 'terbit',
            'dateFrom' => null,
            'dateUntil' => null,
            'category_id' => $kategoriA->id,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Populer Tertinggi', $text);
        $this->assertStringContainsString('Populer Sedang', $text);
        $this->assertStringContainsString('Populer Rendah', $text);
        $this->assertStringNotContainsString('Populer Kategori B', $text);

        $posTinggi = strpos($text, 'Populer Tertinggi');
        $posSedang = strpos($text, 'Populer Sedang');
        $posRendah = strpos($text, 'Populer Rendah');

        $this->assertLessThan($posSedang, $posTinggi);
        $this->assertLessThan($posRendah, $posSedang);
    }

    public function test_status_filter_terjadwal_only_returns_future_publications(): void
    {
        $this->seedPost('Terbit Biasa', ['published_at' => '2026-11-01 09:00:00']);
        Post::factory()->published()->create(['title' => 'Terjadwal Masa Depan', 'published_at' => now()->addDays(10)]);
        Post::factory()->create(['title' => 'Draf Biasa']);

        $rows = $this->writeCsv([
            'jenis' => 'berita',
            'format' => 'csv',
            'status' => 'terjadwal',
            'dateFrom' => null,
            'dateUntil' => null,
            'category_id' => null,
        ]);

        $text = $this->flatText($rows);

        $this->assertStringContainsString('Terjadwal Masa Depan', $text);
        $this->assertStringNotContainsString('Terbit Biasa', $text);
        $this->assertStringNotContainsString('Draf Biasa', $text);
    }

    public function test_report_downloaded_via_page_is_audited(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Laporan::class)
            ->set('data.jenis', 'berita')
            ->set('data.format', 'csv')
            ->call('export');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'report_exported',
            'module' => 'laporan',
        ]);
    }

    public function test_admin_export_page_returns_a_download_response(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->published()->create(['title' => 'Berita Unduh Laporan']);

        $component = Livewire::actingAs($admin)
            ->test(Laporan::class)
            ->set('data.jenis', 'berita')
            ->set('data.format', 'csv')
            ->call('export');

        $this->assertNotEmpty($component->effects['download'] ?? null);
    }
}
