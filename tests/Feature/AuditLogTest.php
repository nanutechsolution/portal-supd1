<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\AuditLog;
use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\AuditLogger;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_post_create_update_publish_and_unpublish_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        $post = Post::factory()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'title' => 'Judul Awal Audit',
            'status' => PostStatus::Draft,
        ]);

        $created = $this->auditFor('created', 'posts', $post->id);
        $this->assertNotNull($created);
        $this->assertSame($admin->id, $created->user_id);
        $this->assertSame($admin->name, $created->actor_name);
        $this->assertSame('Judul Awal Audit', $created->after['title']);
        $this->assertSame('success', $created->status);

        $post->update(['title' => 'Judul Setelah Audit']);
        $post->refresh();

        $updated = $this->auditFor('updated', 'posts', $post->id);
        $this->assertNotNull($updated);
        $this->assertSame('Judul Awal Audit', $updated->before['title']);
        $this->assertSame('Judul Setelah Audit', $updated->after['title']);

        $post->update(['status' => PostStatus::Published]);
        $post->refresh();

        $published = $this->auditFor('published', 'posts', $post->id);
        $this->assertNotNull($published);
        $this->assertSame(PostStatus::Draft->value, $published->before['status']);
        $this->assertSame(PostStatus::Published->value, $published->after['status']);

        $post->update(['status' => PostStatus::Draft]);
        $post->refresh();

        $unpublished = $this->auditFor('unpublished', 'posts', $post->id);
        $this->assertNotNull($unpublished);
        $this->assertSame(PostStatus::Published->value, $unpublished->before['status']);
        $this->assertSame(PostStatus::Draft->value, $unpublished->after['status']);
    }

    public function test_bulletin_create_publish_unpublish_and_delete_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $bulletin = Bulletin::factory()->create([
            'title' => 'Buletin Audit',
            'is_published' => false,
        ]);

        $this->assertNotNull($this->auditFor('created', 'bulletins', $bulletin->id));

        $bulletin->update(['is_published' => true]);
        $published = $this->auditFor('published', 'bulletins', $bulletin->id);
        $this->assertNotNull($published);
        $this->assertFalse((bool) $published->before['is_published']);
        $this->assertTrue((bool) $published->after['is_published']);

        $bulletin->update(['is_published' => false]);
        $this->assertNotNull($this->auditFor('unpublished', 'bulletins', $bulletin->id));

        $bulletinId = $bulletin->id;
        $bulletin->delete();

        $deleted = $this->auditFor('deleted', 'bulletins', $bulletinId);
        $this->assertNotNull($deleted);
        $this->assertSame('Buletin Audit', $deleted->subject_label);
        $this->assertSame('Buletin Audit', $deleted->before['title']);
    }

    public function test_gallery_create_update_media_replacement_and_delete_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $item = GalleryItem::factory()->create([
            'title' => 'Foto Audit',
            'image' => 'gallery/lama.jpg',
        ]);

        $this->assertNotNull($this->auditFor('created', 'gallery', $item->id));

        $item->update(['description' => 'Deskripsi baru.']);

        $updated = $this->auditFor('updated', 'gallery', $item->id);
        $this->assertNotNull($updated);
        $this->assertSame('Deskripsi baru.', $updated->after['description']);

        $item->update(['image' => 'gallery/baru.jpg']);

        $replaced = $this->auditFor('media_replaced', 'gallery', $item->id);
        $this->assertNotNull($replaced);
        $this->assertSame('gallery/lama.jpg', $replaced->before['image']);
        $this->assertSame('gallery/baru.jpg', $replaced->after['image']);

        $item->delete();

        $this->assertNotNull($this->auditFor('deleted', 'gallery', $item->id));
    }

    public function test_organizational_unit_create_publish_unpublish_and_delete_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Unit Audit',
            'is_published' => false,
        ]);

        $this->assertNotNull($this->auditFor('created', 'organizational_units', $unit->id));

        $unit->update(['is_published' => true]);
        $this->assertNotNull($this->auditFor('published', 'organizational_units', $unit->id));

        $unit->update(['is_published' => false]);
        $this->assertNotNull($this->auditFor('unpublished', 'organizational_units', $unit->id));

        $unit->delete();

        $deleted = $this->auditFor('deleted', 'organizational_units', $unit->id);
        $this->assertNotNull($deleted);
        $this->assertSame('Unit Audit', $deleted->subject_label);
    }

    public function test_page_create_update_and_delete_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $page = Page::factory()->create(['title' => 'Halaman Audit']);

        $this->assertNotNull($this->auditFor('created', 'pages', $page->id));

        $page->update(['title' => 'Halaman Setelah Audit']);

        $updated = $this->auditFor('updated', 'pages', $page->id);
        $this->assertNotNull($updated);
        $this->assertSame('Halaman Audit', $updated->before['title']);
        $this->assertSame('Halaman Setelah Audit', $updated->after['title']);

        $page->delete();

        $this->assertNotNull($this->auditFor('deleted', 'pages', $page->id));
    }

    public function test_user_creation_role_change_and_password_updates_are_audited_without_secrets(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $editor = User::factory()->create([
            'name' => 'Editor Audit',
            'email' => 'editor-audit@example.test',
            'password' => 'rahasia-sekali',
            'role' => UserRole::Editor,
        ]);

        $created = $this->auditFor('created', 'users', $editor->id);
        $this->assertNotNull($created);
        $this->assertSame('Editor Audit', $created->subject_label);
        $this->assertArrayNotHasKey('password', $created->after);
        $this->assertArrayNotHasKey('remember_token', $created->after);

        $editor->update(['role' => UserRole::Admin]);

        $roleChanged = $this->auditFor('role_changed', 'users', $editor->id);
        $this->assertNotNull($roleChanged);
        $this->assertSame(UserRole::Editor->value, $roleChanged->before['role']);
        $this->assertSame(UserRole::Admin->value, $roleChanged->after['role']);

        $editor->update(['password' => 'kata-sandi-baru']);

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'users',
            'subject_id' => $editor->id,
            'event' => 'updated',
        ]);

        $serialized = json_encode(AuditLog::query()->get()->toArray());

        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('rahasia-sekali', $serialized);
        $this->assertStringNotContainsString('kata-sandi-baru', $serialized);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('remember_token', $serialized);
    }

    public function test_settings_changes_are_audited_with_before_and_after_values(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'Portal Audit SUPD I',
                'site_short_name' => 'SUPD I',
                'site_tagline' => 'Tagline audit.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = AuditLog::query()
            ->where('module', 'settings')
            ->where('event', 'settings_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Pengaturan Website', $log->subject_label);
        $this->assertArrayHasKey('site_name', $log->after);
        $this->assertSame('Portal Audit SUPD I', $log->after['site_name']);
        $this->assertArrayNotHasKey('site_logo', $log->before);
    }

    public function test_settings_media_replacement_is_audited_and_old_file_is_removed(): void
    {
        $admin = User::factory()->admin()->create();

        Storage::disk('public')->put('settings/site_logo-lama.png', 'logo lama');
        Setting::putMany(['site_logo' => 'settings/site_logo-lama.png']);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => [UploadedFile::fake()->image('logo-baru.png', 400, 200)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = Setting::rawValues()['site_logo'] ?? null;

        $this->assertIsString($newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('settings/site_logo-lama.png');

        $log = AuditLog::query()
            ->where('module', 'settings')
            ->where('event', 'settings_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('settings/site_logo-lama.png', $log->before['site_logo']);
        $this->assertSame($newPath, $log->after['site_logo']);
    }

    public function test_media_replacement_and_removal_are_audited_for_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Storage::disk('public')->put('posts/lama.jpg', 'gambar lama');
        Storage::disk('public')->put('posts/baru.jpg', 'gambar baru');

        $post = Post::factory()->create([
            'category_id' => Category::factory(),
            'user_id' => $admin->id,
            'featured_image' => 'posts/lama.jpg',
        ]);

        $post->update(['featured_image' => 'posts/baru.jpg']);

        $replaced = $this->auditFor('media_replaced', 'posts', $post->id);
        $this->assertNotNull($replaced);
        $this->assertSame('posts/lama.jpg', $replaced->before['featured_image']);
        $this->assertSame('posts/baru.jpg', $replaced->after['featured_image']);
        Storage::disk('public')->assertMissing('posts/lama.jpg');

        $post->update(['featured_image' => null]);

        $removed = $this->auditFor('media_replaced', 'posts', $post->id);
        $this->assertNotNull($removed);
        $this->assertSame('posts/baru.jpg', $removed->before['featured_image']);
        $this->assertArrayHasKey('featured_image', $removed->after);
        $this->assertNull($removed->after['featured_image']);
        Storage::disk('public')->assertMissing('posts/baru.jpg');
    }

    public function test_transaction_rollback_removes_audit_records(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $post = Post::factory()->create([
            'category_id' => Category::factory(),
            'user_id' => $admin->id,
            'title' => 'Judul Sebelum Rollback',
        ]);

        try {
            DB::transaction(function () use ($post): void {
                $post->update(['title' => 'Judul Di Dalam Transaksi']);

                throw new RuntimeException('Simulasi kegagalan bisnis.');
            });
        } catch (RuntimeException) {
            // Expected: the business transaction is rolled back.
        }

        $this->assertSame('Judul Sebelum Rollback', $post->fresh()->title);
        $this->assertNull($this->auditFor('updated', 'posts', $post->id));
    }

    public function test_delete_keeps_audit_history_and_actor_name_after_user_deletion(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Snapshot']);
        $author = User::factory()->editor()->create();
        $this->actingAs($admin);

        $post = Post::factory()->create([
            'category_id' => Category::factory(),
            'user_id' => $author->id,
            'title' => 'Berita Yang Dihapus',
        ]);

        $postId = $post->id;

        $post->delete();

        $deleted = $this->auditFor('deleted', 'posts', $postId);
        $this->assertNotNull($deleted);
        $this->assertSame('Berita Yang Dihapus', $deleted->subject_label);

        $adminId = $admin->id;
        $admin->delete();

        $this->assertDatabaseMissing('users', ['id' => $adminId]);

        $deleted->refresh();
        $this->assertNull($deleted->user_id);
        $this->assertSame('Admin Snapshot', $deleted->actor_name);
        $this->assertSame('Berita Yang Dihapus', $deleted->subject_label);
    }

    public function test_login_failure_success_and_logout_are_audited_without_credentials(): void
    {
        $editor = User::factory()->editor()->create([
            'name' => 'Editor Login',
            'email' => 'editor-login@example.test',
            'password' => 'password',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $editor->email,
                'password' => 'kata-sandi-salah',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $failed = AuditLog::query()
            ->where('module', 'auth')
            ->where('event', 'login_failed')
            ->latest('id')
            ->first();

        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed->status);
        $this->assertSame($editor->id, $failed->user_id);
        $this->assertNull($failed->before);
        $this->assertNull($failed->after);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $editor->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($editor->fresh());

        $login = AuditLog::query()
            ->where('module', 'auth')
            ->where('event', 'login')
            ->latest('id')
            ->first();

        $this->assertNotNull($login);
        $this->assertSame($editor->id, $login->user_id);
        $this->assertSame('Editor Login', $login->actor_name);
        $this->assertSame('success', $login->status);

        $this->actingAs($editor->fresh())->post('/admin/logout');

        $logout = AuditLog::query()
            ->where('module', 'auth')
            ->where('event', 'logout')
            ->latest('id')
            ->first();

        $this->assertNotNull($logout);
        $this->assertSame($editor->id, $logout->user_id);

        $serialized = json_encode(AuditLog::query()->where('module', 'auth')->get()->toArray());

        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('kata-sandi-salah', $serialized);
        $this->assertStringNotContainsString('password', $serialized);
        $this->assertStringNotContainsString('editor-login@example.test', $serialized);
    }

    public function test_administrator_can_access_audit_logs_but_editor_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $log = AuditLog::factory()->create([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs/'.$log->id)->assertOk();

        $this->actingAs($editor)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($editor)->get('/admin/audit-logs/'.$log->id)->assertForbidden();
    }

    public function test_audit_log_is_read_only_in_filament_and_the_model(): void
    {
        $admin = User::factory()->admin()->create();
        $log = AuditLog::factory()->create();

        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertFalse(AuditLogResource::canEdit($log));
        $this->assertFalse(AuditLogResource::canDelete($log));
        $this->assertFalse(AuditLogResource::canDeleteAny());
        $this->assertArrayNotHasKey('create', AuditLogResource::getPages());
        $this->assertArrayNotHasKey('edit', AuditLogResource::getPages());

        $this->actingAs($admin)->get('/admin/audit-logs/create')->assertNotFound();

        Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');

        $updateBlocked = false;

        try {
            $log->update(['event' => 'tampered']);
        } catch (\LogicException) {
            $updateBlocked = true;
        }

        $this->assertTrue($updateBlocked);

        $deleteBlocked = false;

        try {
            $log->delete();
        } catch (\LogicException) {
            $deleteBlocked = true;
        }

        $this->assertTrue($deleteBlocked);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_audit_detail_page_escapes_untrusted_content(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $payload = '<script>alert("xss")</script>';

        $post = Post::factory()->create([
            'category_id' => Category::factory(),
            'user_id' => $admin->id,
            'title' => $payload,
        ]);

        $log = $this->auditFor('created', 'posts', $post->id);

        $this->assertNotNull($log);

        $this->get('/admin/audit-logs/'.$log->id)
            ->assertOk()
            ->assertDontSee($payload, false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_csv_export_contains_only_filtered_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        AuditLogger::record(event: 'created', module: 'posts', subjectLabel: 'Baris CSV Posts');
        AuditLogger::record(event: 'role_changed', module: 'users', subjectLabel: 'Baris CSV Users');

        $component = Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->callAction('exportCsv');

        $component->assertFileDownloaded();

        $download = $component->effects['download'];
        $content = base64_decode((string) $download['content']);

        $this->assertStringEndsWith('.csv', $download['name']);
        $this->assertSame('text/csv; charset=UTF-8', $download['contentType']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Waktu,Pengguna,Aktivitas,Modul', $content);
        $this->assertStringContainsString('Baris CSV Posts', $content);
        $this->assertStringNotContainsString('Baris CSV Users', $content);
    }

    public function test_audit_list_filters_by_user_module_event_and_date(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->editor()->create();

        $postCreated = $this->makeAuditLog([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'event' => 'created',
            'module' => 'posts',
            'subject_label' => 'Baris Posts',
            'created_at' => now(),
        ]);

        $roleChanged = $this->makeAuditLog([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'event' => 'role_changed',
            'module' => 'users',
            'subject_label' => 'Baris Users',
            'created_at' => now(),
        ]);

        $oldDeleted = $this->makeAuditLog([
            'user_id' => $other->id,
            'actor_name' => $other->name,
            'event' => 'deleted',
            'module' => 'posts',
            'subject_label' => 'Baris Lama',
            'created_at' => now()->subDays(60),
        ]);

        $loginFailed = $this->makeAuditLog([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'event' => 'login_failed',
            'module' => 'auth',
            'subject_label' => 'Baris Auth',
            'status' => 'failed',
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListAuditLogs::class)
            ->filterTable('module', 'posts')
            ->assertCanSeeTableRecords([$postCreated, $oldDeleted])
            ->assertCanNotSeeTableRecords([$roleChanged, $loginFailed])
            ->resetTableFilters()
            ->filterTable('event', 'role_changed')
            ->assertCanSeeTableRecords([$roleChanged])
            ->assertCanNotSeeTableRecords([$postCreated, $oldDeleted, $loginFailed])
            ->resetTableFilters()
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$oldDeleted])
            ->assertCanNotSeeTableRecords([$postCreated, $roleChanged, $loginFailed])
            ->resetTableFilters()
            ->filterTable('created_at', [
                'from' => now()->subDay()->toDateString(),
                'until' => now()->toDateString(),
            ])
            ->assertCanSeeTableRecords([$postCreated, $roleChanged, $loginFailed])
            ->assertCanNotSeeTableRecords([$oldDeleted]);
    }

    public function test_audit_list_query_count_does_not_grow_with_record_count(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->makeManyAuditLogs(5);

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($admin)->test(ListAuditLogs::class);
        $queriesWithFiveRows = count(DB::getQueryLog());

        $this->makeManyAuditLogs(35);

        DB::flushQueryLog();
        Livewire::actingAs($admin)->test(ListAuditLogs::class);
        $queriesWithFortyRows = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $queriesWithFiveRows + 1,
            $queriesWithFortyRows,
            "Rendering the audit list used {$queriesWithFortyRows} queries for 40 rows versus {$queriesWithFiveRows} for 5 rows.",
        );
    }

    public function test_retention_command_prunes_only_expired_records(): void
    {
        $old = $this->makeAuditLog(['created_at' => now()->subDays(400)]);
        $recent = $this->makeAuditLog(['created_at' => now()->subDay()]);

        $this->artisan('audit:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }

    public function test_bulk_delete_records_each_deleted_object(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $category = Category::factory()->create();
        $first = Post::factory()->create(['category_id' => $category->id, 'user_id' => $admin->id]);
        $second = Post::factory()->create(['category_id' => $category->id, 'user_id' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('delete', [$first, $second]);

        $this->assertDatabaseMissing('posts', ['id' => $first->id]);
        $this->assertDatabaseMissing('posts', ['id' => $second->id]);
        $this->assertNotNull($this->auditFor('deleted', 'posts', $first->id));
        $this->assertNotNull($this->auditFor('deleted', 'posts', $second->id));
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

    private function makeManyAuditLogs(int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            $this->makeAuditLog([
                'actor_name' => 'Pengguna '.$index,
                'event' => 'created',
                'module' => 'posts',
                'subject_label' => 'Baris '.$index,
                'created_at' => now()->subMinutes($index),
            ]);
        }
    }
}
