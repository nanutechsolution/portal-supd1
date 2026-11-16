<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PostNavigationTest extends TestCase
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

    public function test_post_shows_previous_and_next_published_posts(): void
    {
        $older = Post::factory()->published()->create([
            'title' => 'Berita Lebih Lama Navigasi',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDays(3),
        ]);

        $current = Post::factory()->published()->create([
            'title' => 'Berita Saat Ini Navigasi',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDays(2),
        ]);

        $newer = Post::factory()->published()->create([
            'title' => 'Berita Lebih Baru Navigasi',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $current))
            ->assertOk()
            ->assertSee('Berita Sebelumnya')
            ->assertSee($older->title)
            ->assertSee('Berita Berikutnya')
            ->assertSee($newer->title);
    }

    public function test_oldest_post_only_has_a_next_link(): void
    {
        $oldest = Post::factory()->published()->create([
            'title' => 'Berita Paling Lama Navigasi',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDays(5),
        ]);

        $next = Post::factory()->published()->create([
            'title' => 'Berita Setelah Yang Paling Lama',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDays(4),
        ]);

        $this->get(route('posts.show', $oldest))
            ->assertOk()
            ->assertDontSee('Berita Sebelumnya')
            ->assertSee('Berita Berikutnya')
            ->assertSee($next->title);
    }

    public function test_newest_post_only_has_a_previous_link(): void
    {
        $previous = Post::factory()->published()->create([
            'title' => 'Berita Sebelum Yang Terbaru',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDays(2),
        ]);

        $newest = Post::factory()->published()->create([
            'title' => 'Berita Paling Baru Navigasi',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $newest))
            ->assertOk()
            ->assertSee('Berita Sebelumnya')
            ->assertSee($previous->title)
            ->assertDontSee('Berita Berikutnya');
    }

    public function test_drafts_and_scheduled_posts_are_not_part_of_navigation(): void
    {
        $current = Post::factory()->published()->create([
            'title' => 'Berita Navigasi Aman',
            'category_id' => Category::factory()->create()->id,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'title' => 'Draf Tidak Boleh Muncul Navigasi',
            'published_at' => null,
        ]);

        Post::factory()->published()->create([
            'title' => 'Terjadwal Tidak Boleh Muncul Navigasi',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('posts.show', $current))
            ->assertOk()
            ->assertDontSee('Draf Tidak Boleh Muncul Navigasi')
            ->assertDontSee('Terjadwal Tidak Boleh Muncul Navigasi');
    }

    public function test_posts_with_the_same_timestamp_are_ordered_by_id(): void
    {
        $time = now()->subHours(5);

        $first = Post::factory()->published()->create([
            'title' => 'Berita Waktu Sama Satu',
            'category_id' => Category::factory()->create()->id,
            'published_at' => $time,
        ]);

        $second = Post::factory()->published()->create([
            'title' => 'Berita Waktu Sama Dua',
            'category_id' => Category::factory()->create()->id,
            'published_at' => $time,
        ]);

        $this->get(route('posts.show', $second))
            ->assertOk()
            ->assertSee('Berita Sebelumnya')
            ->assertSee($first->title)
            ->assertDontSee('Berita Berikutnya');
    }

    public function test_draft_preview_still_renders_navigation_without_crashing(): void
    {
        $admin = User::factory()->admin()->create();

        Post::factory()->published()->create([
            'title' => 'Berita Terbit Untuk Pratinjau',
            'published_at' => now()->subDay(),
        ]);

        $draft = Post::factory()->create(['title' => 'Draf Dengan Navigasi']);

        $this->actingAs($admin)
            ->get(route('posts.preview', $draft))
            ->assertOk()
            ->assertSee('Pratinjau Draft')
            ->assertSee('Draf Dengan Navigasi');
    }
}
