<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Http\Controllers\RssFeedController;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_scheduled_post_is_not_visible_before_its_publish_time(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Belum Tayang',
            'published_at' => now()->addDay(),
        ]);

        $this->get('/berita')->assertDontSee('Berita Belum Tayang');
        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_scheduled_post_becomes_visible_after_its_publish_time(): void
    {
        Post::factory()->published()->create([
            'title' => 'Berita Sudah Tayang',
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/berita')->assertSee('Berita Sudah Tayang');
    }

    public function test_publish_scheduled_command_records_published_event_for_newly_due_posts(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Baru Tayang',
            'published_at' => now(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'published',
            'module' => 'posts',
            'subject_type' => Post::class,
            'subject_id' => $post->id,
        ]);
    }

    public function test_publish_scheduled_command_is_idempotent_and_skips_old_posts(): void
    {
        $old = Post::factory()->published()->create([
            'title' => 'Berita Lama',
            'published_at' => now()->subDays(10),
        ]);
        $recent = Post::factory()->published()->create([
            'title' => 'Berita Baru',
            'published_at' => now(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();
        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame(0, AuditLog::query()
            ->where('event', 'published')
            ->where('subject_type', Post::class)
            ->where('subject_id', $old->id)
            ->count());

        $this->assertSame(1, AuditLog::query()
            ->where('event', 'published')
            ->where('subject_type', Post::class)
            ->where('subject_id', $recent->id)
            ->count());
    }

    public function test_publish_scheduled_command_clears_the_rss_cache_when_posts_publish(): void
    {
        Cache::put(RssFeedController::CACHE_KEY, ['dummy' => true], 60);

        Post::factory()->published()->create([
            'title' => 'Berita Cache RSS',
            'published_at' => now(),
        ]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertNull(Cache::get(RssFeedController::CACHE_KEY));
    }

    public function test_bulk_publish_publishes_drafts_and_newly_due_scheduled_posts(): void
    {
        $admin = User::factory()->admin()->create();

        $draft = Post::factory()->create(['title' => 'Draf Bulk Publikasi']);
        $scheduled = Post::factory()->published()->create([
            'title' => 'Terjadwal Bulk Publikasi',
            'published_at' => now()->addDay(),
        ]);
        $alreadyLive = Post::factory()->published()->create([
            'title' => 'Tayang Bulk Publikasi',
            'published_at' => now()->subWeek(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', [$draft, $scheduled, $alreadyLive]);

        $this->assertSame('published', $draft->refresh()->status->value);
        $this->assertSame('published', $scheduled->refresh()->status->value);
        $this->assertTrue($scheduled->refresh()->published_at->isPast());

        // Berita yang sudah tayang tidak ditulis ulang.
        $this->assertTrue($alreadyLive->refresh()->published_at->isPast());

        $this->assertDatabaseHas('audit_logs', ['event' => 'published', 'subject_id' => $draft->id]);
    }

    public function test_bulk_draft_unpublishes_selected_posts(): void
    {
        $admin = User::factory()->admin()->create();

        $first = Post::factory()->published()->create(['title' => 'Bulk Draf Satu', 'published_at' => now()->subDay()]);
        $second = Post::factory()->published()->create(['title' => 'Bulk Draf Dua', 'published_at' => now()->subDay()]);
        $publishedAt = $first->published_at;

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('draft', [$first, $second]);

        $this->assertSame('draft', $first->refresh()->status->value);
        $this->assertSame('draft', $second->refresh()->status->value);
        $this->assertTrue($publishedAt->eq($first->published_at));
    }

    public function test_bulk_schedule_sets_status_and_future_time(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Post::factory()->create(['title' => 'Bulk Jadwalkan']);
        $when = now()->addDays(2)->setTime(9, 30);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('schedule', [$draft], ['published_at' => $when->toDateTimeString()]);

        $draft->refresh();

        $this->assertSame('published', $draft->status->value);
        $this->assertTrue($draft->published_at->isFuture());
        $this->assertDatabaseMissing('audit_logs', ['event' => 'audit_log_viewed', 'subject_id' => $draft->id]);
    }

    public function test_bulk_change_category_moves_selected_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $oldCategory = Category::factory()->create(['name' => 'Kategori Lama']);
        $newCategory = Category::factory()->create(['name' => 'Kategori Baru']);

        $first = Post::factory()->published()->create(['category_id' => $oldCategory->id, 'title' => 'Bulk Kategori Satu']);
        $second = Post::factory()->published()->create(['category_id' => $oldCategory->id, 'title' => 'Bulk Kategori Dua']);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('changeCategory', [$first, $second], ['category_id' => $newCategory->id]);

        $this->assertSame($newCategory->id, $first->refresh()->category_id);
        $this->assertSame($newCategory->id, $second->refresh()->category_id);
    }

    public function test_bulk_actions_create_audit_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = Post::factory()->create(['title' => 'Bulk Audit Draf']);
        $published = Post::factory()->published()->create(['title' => 'Bulk Audit Publikasi']);

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', [$draft])
            ->callTableBulkAction('draft', [$published]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'published', 'subject_id' => $draft->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'unpublished', 'subject_id' => $published->id]);
    }

    public function test_editor_can_use_publication_bulk_actions(): void
    {
        $editor = User::factory()->editor()->create();
        $draft = Post::factory()->create(['title' => 'Bulk Editor Publikasi']);

        Livewire::actingAs($editor)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', [$draft]);

        $this->assertSame('published', $draft->refresh()->status->value);
    }

    public function test_guest_cannot_access_posts_page(): void
    {
        $this->get('/admin/posts')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_bulk_publish_on_empty_selection_is_safe(): void
    {
        $admin = User::factory()->admin()->create();
        $before = Post::query()->count();

        Livewire::actingAs($admin)
            ->test(ListPosts::class)
            ->callTableBulkAction('publish', []);

        $this->assertSame($before, Post::query()->count());
    }

    public function test_application_timezone_is_used_for_scheduling(): void
    {
        $this->assertSame(env('APP_TIMEZONE', 'Asia/Jakarta'), config('app.timezone'));
        $this->assertSame(config('app.timezone'), now()->timezoneName);
    }
}
