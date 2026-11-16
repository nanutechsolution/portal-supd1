<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Otorisasi halaman Audit Log: hanya administrator yang boleh melihat atau
 * mengekspor. HTTP dan Livewire yang sesungguhnya dipakai, dan setiap skenario
 * menegaskan isi database, bukan hanya status HTTP.
 */
class AuditLogAuthorizationTest extends TestCase
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

    public function test_guest_is_redirected_to_login_and_writes_no_view_event(): void
    {
        $log = AuditLog::factory()->create();

        $this->get('/admin/audit-logs/'.$log->id)->assertRedirect('/admin/login');

        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_viewed')->count());
        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_exported')->count());
    }

    public function test_editor_cannot_view_detail_and_no_view_event_is_written(): void
    {
        $editor = User::factory()->editor()->create();
        $log = AuditLog::factory()->create(['subject_label' => 'Baris yang tidak boleh terlihat']);

        $this->actingAs($editor)->get('/admin/audit-logs/'.$log->id)->assertForbidden();

        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_viewed')->count());
    }

    public function test_admin_viewing_detail_writes_exactly_one_view_event(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::factory()->create();

        $this->actingAs($admin)->get('/admin/audit-logs/'.$log->id)->assertOk();

        $viewed = AuditLog::query()->where('event', 'audit_log_viewed')->get();

        $this->assertCount(1, $viewed);
        $this->assertSame($admin->id, $viewed->first()->user_id);
        $this->assertSame($admin->name, $viewed->first()->actor_name);
        $this->assertSame('audit_logs', $viewed->first()->module);
        $this->assertSame($log->id, $viewed->first()->subject_id);
        $this->assertSame('success', $viewed->first()->status);
    }

    public function test_editor_cannot_open_the_list_page_and_no_events_are_written(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/audit-logs')->assertForbidden();

        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_viewed')->count());
        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_exported')->count());
    }

    public function test_admin_can_open_the_list_page_without_unwanted_events(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::factory()->count(2)->create();

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();

        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_viewed')->count());
        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_exported')->count());
    }

    public function test_editor_cannot_trigger_csv_export(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/audit-logs')->assertForbidden();

        // Entry point blokiran: komponen tidak pernah terpasang untuk editor,
        // sehingga aksi ekspor tidak dapat dieksekusi.
        $this->assertNull(
            Livewire::actingAs($editor)->test(ListAuditLogs::class)->instance(),
        );

        $this->assertSame(0, AuditLog::query()->where('event', 'audit_log_exported')->count());
    }

    public function test_admin_can_export_csv_and_exactly_one_export_event_is_written(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        AuditLog::factory()->create(['module' => 'posts', 'subject_label' => 'Ekspor Satu']);
        AuditLog::factory()->create(['module' => 'posts', 'subject_label' => 'Ekspor Dua']);

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $exported = AuditLog::query()->where('event', 'audit_log_exported')->get();

        $this->assertCount(1, $exported);
        $this->assertSame($admin->id, $exported->first()->user_id);
        $this->assertSame('audit_logs', $exported->first()->module);
        $this->assertSame(2, $exported->first()->after['rows_snapshot']);
        $this->assertSame(2, $exported->first()->after['rows_emitted']);
    }
}
