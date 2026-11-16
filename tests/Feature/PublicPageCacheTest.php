<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicPageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['portal.public_cache.ttl' => 3600]);

        Setting::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_guest_pages_are_cached_after_the_first_render(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Berita Cache Halaman Depan']);

        $this->get('/')
            ->assertOk()
            ->assertSee($post->title)
            ->assertHeader('X-Page-Cache', 'MISS');

        $this->get('/')
            ->assertOk()
            ->assertSee($post->title)
            ->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_creating_and_editing_content_invalidates_the_cache(): void
    {
        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $post = Post::factory()->published()->create(['title' => 'Berita Baru Setelah Cache']);

        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Berita Baru Setelah Cache');

        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $post->update(['title' => 'Judul Berita Setelah Diperbarui']);

        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Judul Berita Setelah Diperbarui')
            ->assertDontSee('Berita Baru Setelah Cache');
    }

    public function test_category_and_author_changes_invalidate_the_cache(): void
    {
        $category = Category::factory()->create(['name' => 'Rubrik Cache Awal']);
        $author = User::factory()->create(['name' => 'Penulis Cache Awal']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'user_id' => $author->id,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $category->update(['name' => 'Rubrik Cache Diperbarui']);

        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Rubrik Cache Diperbarui');
    }

    public function test_settings_changes_invalidate_the_cache(): void
    {
        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        Setting::putMany(['contact_phone' => '021-9999999']);

        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('021-9999999');
    }

    public function test_view_increments_do_not_invalidate_the_cache(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Hitungan Views',
            'published_at' => now()->subHour(),
        ]);

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $this->get(route('posts.show', $post))->assertOk();

        $this->assertSame(1, (int) $post->refresh()->views);

        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_bulletin_download_increments_do_not_invalidate_the_cache(): void
    {
        $bulletin = Bulletin::factory()->published()->create();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');
        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        $bulletin->increment('downloads');

        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');
    }

    public function test_scheduled_posts_appear_as_soon_as_the_publication_time_passes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-16 09:00:00'));

        Post::factory()->published()->create([
            'title' => 'Berita Terjadwal Cache',
            'published_at' => CarbonImmutable::parse('2026-11-16 09:30:00'),
        ]);

        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertDontSee('Berita Terjadwal Cache');

        $this->get('/')->assertHeader('X-Page-Cache', 'HIT');

        // Masih sebelum waktu terbit: kunci cache belum berubah.
        $this->travelTo(CarbonImmutable::parse('2026-11-16 09:10:00'));
        $this->get('/')
            ->assertHeader('X-Page-Cache', 'HIT')
            ->assertDontSee('Berita Terjadwal Cache');

        // Melewati batas publikasi: kunci cache berganti walau TTL belum habis.
        $this->travelTo(CarbonImmutable::parse('2026-11-16 09:31:00'));
        $this->get('/')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Berita Terjadwal Cache');
    }

    public function test_authenticated_users_always_receive_a_fresh_render(): void
    {
        $admin = User::factory()->admin()->create();

        $this->get('/')->assertHeader('X-Page-Cache', 'MISS');

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertHeaderMissing('X-Page-Cache');
    }

    public function test_cache_can_be_disabled_through_configuration(): void
    {
        config(['portal.public_cache.enabled' => false]);

        $this->get('/')->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get('/')->assertOk()->assertHeaderMissing('X-Page-Cache');
    }

    public function test_article_detail_pages_are_never_cached_so_view_counts_stay_accurate(): void
    {
        $post = Post::factory()->published()->create(['published_at' => now()->subHour()]);

        $this->get(route('posts.show', $post))->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get(route('posts.show', $post))->assertOk()->assertHeaderMissing('X-Page-Cache');

        $this->assertSame(2, (int) $post->refresh()->views);
    }

    public function test_contact_and_search_pages_are_not_cached(): void
    {
        $this->get(route('contact.index'))->assertOk()->assertHeaderMissing('X-Page-Cache');
        $this->get(route('search.index', ['q' => 'berita']))->assertOk()->assertHeaderMissing('X-Page-Cache');
    }

    public function test_pages_with_different_query_strings_use_separate_cache_entries(): void
    {
        Post::factory()->published()->create([
            'title' => 'Berita Alpha Cache',
            'published_at' => now()->subHour(),
        ]);
        Post::factory()->published()->create([
            'title' => 'Berita Beta Cache',
            'published_at' => now()->subMinutes(30),
        ]);

        $this->get('/berita?q=alpha')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Berita Alpha Cache');

        $this->get('/berita?q=beta')
            ->assertHeader('X-Page-Cache', 'MISS')
            ->assertSee('Berita Beta Cache');

        $this->get('/berita?q=alpha')
            ->assertHeader('X-Page-Cache', 'HIT')
            ->assertSee('Berita Alpha Cache');
    }
}
