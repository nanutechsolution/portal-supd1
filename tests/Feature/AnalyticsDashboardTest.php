<?php

namespace Tests\Feature;

use App\Filament\Widgets\TopPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\PortalAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_analitik_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/analitik')
            ->assertOk()
            ->assertSee('Analitik')
            ->assertSee('Ringkasan')
            ->assertSee('Publikasi per Periode')
            ->assertSee('Kunjungan per Periode')
            ->assertSee('Distribusi per Kategori')
            ->assertSee('Berita Paling Banyak Dibaca');
    }

    public function test_editor_cannot_open_analitik_page(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/analitik')->assertForbidden();
    }

    public function test_analitik_renders_with_zero_stats_on_empty_database(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/analitik')
            ->assertOk()
            ->assertSee('0');
    }

    public function test_analytics_counts_publications_drafts_scheduled_and_totals(): void
    {
        Post::factory()->published()->count(2)->create(['published_at' => now()->subDays(10)]);
        Post::factory()->published()->create();
        Post::factory()->published()->create(['published_at' => now()->addDays(3)]);
        Post::factory()->count(3)->create(); // draf (default factory)

        $this->assertSame(3, PortalAnalytics::publishedInPeriod('30'));
        $this->assertSame(3, PortalAnalytics::publishedTotal());
        $this->assertSame(3, PortalAnalytics::draftCount());
        $this->assertSame(1, PortalAnalytics::scheduledCount());
    }

    public function test_views_in_period_uses_daily_rollup(): void
    {
        $post = Post::factory()->published()->create();

        DB::table('post_view_statistics')->insert([
            ['post_id' => $post->id, 'view_date' => now()->toDateString(), 'views' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['post_id' => $post->id, 'view_date' => now()->subDays(10)->toDateString(), 'views' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(3, PortalAnalytics::viewsInPeriod('7'));
        $this->assertSame(8, PortalAnalytics::viewsInPeriod('30'));
        $this->assertSame(8, PortalAnalytics::viewsInPeriod('year'));
        $this->assertSame(0, PortalAnalytics::viewsInPeriod('90') - 8);
    }

    public function test_public_visit_records_daily_rollup(): void
    {
        $post = Post::factory()->published()->create(['views' => 0]);

        $this->get(route('posts.show', $post))->assertOk();

        $this->assertDatabaseHas('post_view_statistics', [
            'post_id' => $post->id,
            'view_date' => now()->toDateString(),
            'views' => 1,
        ]);
        $this->assertSame(1, $post->refresh()->views);

        $this->get(route('posts.show', $post))->assertOk();
        $this->assertDatabaseHas('post_view_statistics', [
            'post_id' => $post->id,
            'view_date' => now()->toDateString(),
            'views' => 2,
        ]);

        $this->assertSame(2, PortalAnalytics::viewsInPeriod('7'));
    }

    public function test_analytics_cache_is_invalidated_on_public_visit(): void
    {
        $post = Post::factory()->published()->create(['views' => 0]);

        PortalAnalytics::snapshot('30'); // cache terisi (0 kunjungan)

        $this->get(route('posts.show', $post))->assertOk();

        $snapshot = PortalAnalytics::snapshot('30');

        $this->assertSame(1, $snapshot['viewsInPeriod']);
    }

    public function test_admin_analitik_visit_does_not_increment_views(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->published()->create(['views' => 7]);

        $this->actingAs($admin)->get('/admin/analitik')->assertOk();

        $this->assertSame(7, $post->refresh()->views);
        $this->assertSame(0, DB::table('post_view_statistics')->count());
    }

    public function test_category_distribution_returns_names_and_counts(): void
    {
        $a = Category::factory()->create(['name' => 'Distribusi Satu']);
        $b = Category::factory()->create(['name' => 'Distribusi Dua']);

        Post::factory()->published()->count(2)->create([
            'category_id' => $a->id,
            'published_at' => now()->subDays(2),
        ]);
        Post::factory()->published()->create([
            'category_id' => $b->id,
            'published_at' => now()->subDays(3),
        ]);

        $distribution = PortalAnalytics::categoryDistribution('30');

        $this->assertContains('Distribusi Satu', $distribution['labels']);
        $this->assertContains('Distribusi Dua', $distribution['labels']);
        $this->assertSame(3, array_sum($distribution['data']));

        $index = array_search('Distribusi Satu', $distribution['labels'], true);
        $this->assertSame(2, $distribution['data'][$index]);
    }

    public function test_publication_buckets_sum_matches_published_in_period(): void
    {
        Post::factory()->published()->count(4)->create(['published_at' => now()->subDays(2)]);
        Post::factory()->published()->count(2)->create(['published_at' => now()->subDays(40)]);

        $buckets = PortalAnalytics::publicationBuckets('30');

        $this->assertSame(4, array_sum($buckets['data']));
        $this->assertSame(count($buckets['labels']), count($buckets['data']));

        $buckets7 = PortalAnalytics::publicationBuckets('7');

        $this->assertSame(4, array_sum($buckets7['data']));

        $bucketsYear = PortalAnalytics::publicationBuckets('year');

        $this->assertSame(12, count($bucketsYear['labels']));
        $this->assertSame(6, array_sum($bucketsYear['data']));
    }

    public function test_normalize_period_falls_back_to_30_days(): void
    {
        $this->assertSame('30', PortalAnalytics::normalizePeriod('tidak-valid'));
        $this->assertSame('7', PortalAnalytics::normalizePeriod('7'));
        $this->assertSame('7', PortalAnalytics::normalizePeriod(7));
        $this->assertSame('year', PortalAnalytics::normalizePeriod('year'));
    }

    public function test_top_posts_widget_lists_highest_view_posts_in_order(): void
    {
        $low = Post::factory()->published()->create(['title' => 'Rendah', 'views' => 10]);
        $high = Post::factory()->published()->create(['title' => 'Tertinggi', 'views' => 500]);
        $mid = Post::factory()->published()->create(['title' => 'Sedang', 'views' => 100]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(TopPosts::class)
            ->assertCanSeeTableRecords([$high, $mid, $low], true);
    }
}
