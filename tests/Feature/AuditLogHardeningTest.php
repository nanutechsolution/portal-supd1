<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogHardeningTest extends TestCase
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

    public function test_csv_export_neutralizes_spreadsheet_formula_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $payloads = [
            '=1+1',
            '+SUM(A1:A2)',
            '-2+3',
            '@SUM(A1)',
            '  =cmd|calc',
            "\t=cmd|calc",
            "\r=cmd|calc",
            "\x0b@SUM(A1)",
            '=HYPERLINK("http://evil.test","klik")',
        ];

        foreach ($payloads as $payload) {
            AuditLog::query()->create([
                'actor_name' => $payload,
                'event' => 'created',
                'module' => 'posts',
                'subject_label' => $payload,
                'user_agent' => $payload,
                'ip_address' => '127.0.0.1',
                'before' => ['note' => $payload],
                'after' => ['note' => $payload],
                'status' => 'success',
                'created_at' => now(),
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $download = $component->effects['download'];
        $content = base64_decode((string) $download['content']);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertSame('text/csv; charset=UTF-8', $download['contentType']);

        $rows = $this->parseCsv($content);
        $header = array_shift($rows);

        $this->assertSame('Waktu', $header[0]);
        $this->assertCount(count($payloads), $rows);

        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^[\s\x00-\x1F]*[=+\-@]/',
                    (string) $cell,
                    'Nilai sel CSV tidak boleh diawali karakter formula.',
                );
            }
        }

        foreach ($rows as $row) {
            // Actor, object label, and user agent are all neutralized.
            $this->assertSame($row[1], $row[4]);
            $this->assertSame($row[9], $row[4]);
            $this->assertSame("'", substr((string) $row[4], 0, 1));

            // The JSON payload columns stay valid JSON because they start with "{".
            $this->assertIsArray(json_decode((string) $row[10], true));
            $this->assertIsArray(json_decode((string) $row[11], true));
            $this->assertSame(ltrim((string) $row[4], "'"), json_decode((string) $row[10], true)['note']);
        }

        $this->assertDatabaseHas('audit_logs', ['module' => 'posts', 'subject_label' => '=1+1']);
    }

    public function test_csv_export_keeps_benign_text_and_numbers_unchanged(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Audit']);
        $this->actingAs($admin);

        $log = AuditLog::query()->create([
            'actor_name' => 'Admin Audit',
            'event' => 'created',
            'module' => 'posts',
            'subject_type' => 'App\\Models\\Post',
            'subject_id' => 42,
            'subject_label' => 'Laporan Keuangan 2026',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'ip_address' => '127.0.0.1',
            'before' => null,
            'after' => ['tahun' => 2026],
            'status' => 'success',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $rows = $this->parseCsv(base64_decode((string) $component->effects['download']['content']));
        array_shift($rows);

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertSame($log->created_at->format('Y-m-d H:i:s'), $row[0]);
        $this->assertSame('Admin Audit', $row[1]);
        $this->assertSame('Dibuat', $row[2]);
        $this->assertSame('Berita', $row[3]);
        $this->assertSame('Laporan Keuangan 2026', $row[4]);
        $this->assertSame('App\\Models\\Post', $row[5]);
        $this->assertSame('42', $row[6]);
        $this->assertSame('Berhasil', $row[7]);
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', $row[9]);
        $this->assertStringStartsWith('{', (string) $row[11]);
    }

    public function test_sensitive_keys_are_redacted_recursively_and_by_format_variant(): void
    {
        $payload = [
            'title' => 'Judul aman',
            'profile' => [
                'display_name' => 'Nama tampilan aman',
                'accessToken' => 'secret-access-token',
                'nested' => [
                    'clientSecret' => 'secret-client',
                    'endpoint' => 'https://example.test/hook',
                ],
            ],
            'user' => [
                'name' => 'Nama aman',
                'credentials' => [
                    'access_token' => 'secret-access',
                    'refreshToken' => 'secret-refresh',
                ],
            ],
            'settings' => [
                'integration' => [
                    'client_secret' => 'secret-integration',
                    'apiKey' => 'secret-api-key',
                    'timeout' => 30,
                ],
            ],
            'api_key' => 'secret-api-key-top',
            'API-KEY' => 'secret-api-key-header',
            'X-Api-Key' => 'secret-api-header',
            'remember_token' => 'secret-remember',
            'rememberToken' => 'secret-remember-camel',
            'password' => 'secret-password',
            'PasswordConfirmation' => 'secret-confirmation',
            'authorization' => 'Bearer secret-token',
            'AuthorizationHeader' => 'Bearer secret-header',
            'cookie' => 'session=abc',
            'cookies' => ['session' => 'abc'],
            'session_id' => 'secret-session',
            'sessionId' => 'secret-session-camel',
            'privateKey' => 'secret-private-key',
            'author' => 'Penulis aman',
            'secretary' => 'Sekretaris aman',
            'monkey' => 'Bukan rahasia',
            'public_key' => 'kunci-publik',
            'description' => 'Deskripsi aman',
        ];

        $clean = AuditLogger::sanitize($payload);

        $this->assertIsArray($clean);

        // Recursive redaction below non-sensitive parents.
        $this->assertArrayNotHasKey('accessToken', $clean['profile']);
        $this->assertArrayNotHasKey('clientSecret', $clean['profile']['nested']);
        $this->assertSame('https://example.test/hook', $clean['profile']['nested']['endpoint']);
        $this->assertSame('Nama tampilan aman', $clean['profile']['display_name']);

        // Entirely sensitive branches are dropped.
        $this->assertArrayNotHasKey('credentials', $clean['user']);
        $this->assertSame('Nama aman', $clean['user']['name']);
        $this->assertArrayNotHasKey('client_secret', $clean['settings']['integration']);
        $this->assertArrayNotHasKey('apiKey', $clean['settings']['integration']);
        $this->assertSame(30, $clean['settings']['integration']['timeout']);

        // Separator and casing variants at the top level.
        foreach ([
            'api_key',
            'API-KEY',
            'X-Api-Key',
            'remember_token',
            'rememberToken',
            'password',
            'PasswordConfirmation',
            'authorization',
            'AuthorizationHeader',
            'cookie',
            'cookies',
            'session_id',
            'sessionId',
            'privateKey',
        ] as $key) {
            $this->assertArrayNotHasKey($key, $clean, "Kunci sensitif [{$key}] harus dihapus.");
        }

        // Benign fields survive.
        $this->assertSame('Penulis aman', $clean['author']);
        $this->assertSame('Sekretaris aman', $clean['secretary']);
        $this->assertSame('Bukan rahasia', $clean['monkey']);
        $this->assertSame('kunci-publik', $clean['public_key']);
        $this->assertSame('Deskripsi aman', $clean['description']);
        $this->assertSame('Judul aman', $clean['title']);

        $serialized = (string) json_encode($clean);

        foreach ([
            'secret-access-token',
            'secret-client',
            'secret-access',
            'secret-refresh',
            'secret-integration',
            'secret-api-key',
            'secret-remember',
            'secret-password',
            'secret-confirmation',
            'Bearer secret-token',
            'session=abc',
            'secret-session',
            'secret-private-key',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
    }

    public function test_viewing_an_audit_log_records_a_view_event_without_recursion(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::factory()->create([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'module' => 'posts',
            'event' => 'deleted',
            'subject_label' => 'Berita yang dilihat',
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs/'.$log->id)->assertOk();

        $viewed = AuditLog::query()->where('event', 'audit_log_viewed')->get();

        $this->assertCount(1, $viewed);
        $this->assertSame($admin->id, $viewed->first()->user_id);
        $this->assertSame('audit_logs', $viewed->first()->module);
        $this->assertSame(AuditLog::class, $viewed->first()->subject_type);
        $this->assertSame($log->id, $viewed->first()->subject_id);
        $this->assertSame('deleted', $viewed->first()->after['event']);

        // Viewing the view-event itself records one more view and stops there.
        $this->actingAs($admin)->get('/admin/audit-logs/'.$viewed->first()->id)->assertOk();

        $this->assertSame(2, AuditLog::query()->where('event', 'audit_log_viewed')->count());
        $this->assertSame(1, AuditLog::query()
            ->where('event', 'audit_log_viewed')
            ->where('subject_id', $viewed->first()->id)
            ->count());
    }

    public function test_exporting_audit_logs_is_audited_with_filters_and_row_count(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        AuditLogger::record(event: 'created', module: 'posts', subjectLabel: 'Ekspor Satu');
        AuditLogger::record(event: 'created', module: 'posts', subjectLabel: 'Ekspor Dua');
        AuditLogger::record(event: 'created', module: 'users', subjectLabel: 'Bukan Target');

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->latest('id')->first();

        $this->assertNotNull($exported);
        $this->assertSame('audit_logs', $exported->module);
        $this->assertSame('success', $exported->status);
        $this->assertSame($admin->id, $exported->user_id);
        $this->assertSame($admin->name, $exported->actor_name);
        $this->assertSame(2, $exported->after['rows_snapshot']);
        $this->assertSame(2, $exported->after['rows_emitted']);
        $this->assertGreaterThan(0, $exported->after['last_id']);
        $this->assertSame('csv', $exported->after['format']);
        $this->assertSame('posts', $exported->after['filters']['module']['value'] ?? $exported->after['filters']['module']);

        // No exported file contents (or row payloads) are written to the audit log.
        $this->assertArrayNotHasKey('content', $exported->after);
        $this->assertArrayNotHasKey('file', $exported->after);
        $this->assertStringNotContainsString('Ekspor Satu', (string) json_encode($exported->toArray()));

        $rows = $this->parseCsv(base64_decode((string) $component->effects['download']['content']));
        array_shift($rows);
        $this->assertCount(2, $rows);

        // An unfiltered export includes earlier events but never itself.
        $totalBeforeSecondExport = AuditLog::query()->count();

        $all = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->callAction('exportCsv');

        $allRows = $this->parseCsv(base64_decode((string) $all->effects['download']['content']));
        array_shift($allRows);

        $exportedRows = array_values(array_filter(
            $allRows,
            fn (array $row): bool => ($row[2] ?? null) === 'Audit log diekspor',
        ));

        $this->assertCount(1, $exportedRows);
        $this->assertCount($totalBeforeSecondExport, $allRows);
    }

    public function test_category_create_update_and_delete_are_audited_through_filament(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Kategori Audit',
                'sort_order' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::query()->where('name', 'Kategori Audit')->firstOrFail();

        $created = $this->auditFor('created', 'categories', $category->id);

        $this->assertNotNull($created);
        $this->assertSame('Kategori Audit', $created->subject_label);
        $this->assertSame('Kategori Audit', $created->after['name']);
        $this->assertSame('success', $created->status);
        $this->assertSame($admin->id, $created->user_id);

        Livewire::actingAs($admin)
            ->test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['name' => 'Kategori Audit Diubah'])
            ->call('save')
            ->assertHasNoFormErrors();

        $updated = $this->auditFor('updated', 'categories', $category->id);

        $this->assertNotNull($updated);
        $this->assertSame('Kategori Audit', $updated->before['name']);
        $this->assertSame('Kategori Audit Diubah', $updated->after['name']);

        $category->refresh();

        Livewire::actingAs($admin)
            ->test(ListCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $deleted = $this->auditFor('deleted', 'categories', $category->id);

        $this->assertNotNull($deleted);
        $this->assertSame('Kategori Audit Diubah', $deleted->subject_label);
        $this->assertSame('Kategori Audit Diubah', $deleted->before['name']);
    }

    public function test_retention_command_only_deletes_past_cutoff_and_is_idempotent(): void
    {
        $expired = $this->makeAuditLog(['created_at' => now()->subDays(31)]);
        $boundary = $this->makeAuditLog(['created_at' => now()->subDays(30)->addHour()]);
        $recent = $this->makeAuditLog(['created_at' => now()->subDay()]);

        $this->artisan('audit:prune', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $expired->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $boundary->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);

        $this->assertSame(2, AuditLog::query()->count());

        $this->artisan('audit:prune', ['--days' => 30])
            ->expectsOutputToContain('Audit log dihapus: 0 baris')
            ->assertSuccessful();

        $this->assertSame(2, AuditLog::query()->count());
        $this->assertDatabaseHas('audit_logs', ['id' => $boundary->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);

        // The retention default is documented and unchanged.
        $this->assertSame(365, (int) config('audit.retention_days'));
    }

    private function auditFor(string $event, string $module, int $subjectId): ?AuditLog
    {
        return AuditLog::query()
            ->where('event', $event)
            ->where('module', $module)
            ->where('subject_id', $subjectId)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeAuditLog(array $attributes = []): AuditLog
    {
        return AuditLog::query()->create(array_merge([
            'actor_name' => 'Sistem',
            'event' => 'created',
            'module' => 'posts',
            'subject_label' => 'Objek Uji',
            'status' => 'success',
        ], $attributes));
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

        return $rows;
    }
}
