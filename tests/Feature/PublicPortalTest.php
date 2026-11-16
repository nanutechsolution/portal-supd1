<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_published_posts_and_hides_drafts(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Berita Terbit Halaman Depan']);
        $draft = Post::factory()->create(['title' => 'Berita Draf Halaman Depan']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Berita Terbit Halaman Depan')
            ->assertDontSee('Berita Draf Halaman Depan');

        $this->assertNotEquals($published->status, $draft->status);
    }

    public function test_homepage_handles_empty_state_without_posts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada berita yang diterbitkan');
    }

    public function test_published_post_detail_is_accessible(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Detail Berita Publik',
            'content' => '<p>Isi berita yang dapat dibaca publik.</p>',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Detail Berita Publik')
            ->assertSee('Isi berita yang dapat dibaca publik.')
            ->assertSee($post->category->name);
    }

    public function test_draft_post_detail_returns_not_found(): void
    {
        $post = Post::factory()->create(['title' => 'Draf Rahasia']);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_post_scheduled_in_the_future_is_not_visible(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Berita Terjadwal',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('posts.show', $post))->assertNotFound();
        $this->get('/berita')->assertDontSee('Berita Terjadwal');
    }

    public function test_news_index_can_search_posts(): void
    {
        Post::factory()->published()->create(['title' => 'Sinkronisasi Pertanian Daerah']);
        Post::factory()->published()->create(['title' => 'Rapat Kehutanan Nasional']);

        $this->get('/berita?q=pertanian')
            ->assertOk()
            ->assertSee('Sinkronisasi Pertanian Daerah')
            ->assertSee('(1 berita)');
    }

    public function test_news_index_paginates_results(): void
    {
        Post::factory()->published()->count(10)->sequence(
            fn ($sequence) => ['published_at' => now()->subDays($sequence->index + 1)],
        )->create();

        $oldest = Post::query()->published()->orderBy('published_at')->first();

        $this->get('/berita')
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertDontSee($oldest->title);

        $this->get('/berita?page=2')
            ->assertOk()
            ->assertSee($oldest->title);
    }

    public function test_category_page_only_shows_its_posts(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);
        $otherCategory = Category::factory()->create(['name' => 'Kehutanan Uji']);

        $matching = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Lingkungan',
        ]);

        $other = Post::factory()->published()->create([
            'category_id' => $otherCategory->id,
            'title' => 'Berita Kehutanan',
        ]);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($other->title);
    }

    public function test_category_page_can_search_within_rubrik(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Program Pertanian Daerah',
        ]);
        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Rapat Kehutanan',
        ]);

        $this->get(route('categories.show', $category).'?q=pertanian')
            ->assertOk()
            ->assertSee('Program Pertanian Daerah')
            ->assertDontSee('Rapat Kehutanan')
            ->assertSee('(1 berita)');
    }

    public function test_category_page_shows_empty_result_for_unmatched_search(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Rapat Kehutanan',
        ]);

        $this->get(route('categories.show', $category).'?q=kata-tidak-ada-xx')
            ->assertOk()
            ->assertSee('Berita tidak ditemukan')
            ->assertDontSee('Rapat Kehutanan');
    }

    public function test_category_page_can_sort_by_popularity(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Kurang Dibaca',
            'views' => 5,
        ]);
        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Paling Dibaca',
            'views' => 900,
        ]);

        $html = $this->get(route('categories.show', $category).'?sort=terpopuler')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Berita Kurang Dibaca'),
            strpos($html, 'Berita Paling Dibaca'),
        );
    }

    public function test_category_pagination_preserves_search_query(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);

        Post::factory()->published()->count(11)->sequence(
            fn ($sequence) => ['title' => 'Pertanian Berita '.$sequence->index, 'published_at' => now()->subDays($sequence->index + 1)],
        )->create(['category_id' => $category->id]);

        $firstPage = $this->get(route('categories.show', $category).'?q=Pertanian&page=1')->assertOk();

        $firstPage->assertSee('page=2', false);
        $this->assertStringContainsString('q=Pertanian', $firstPage->getContent());

        $this->get(route('categories.show', $category).'?q=Pertanian&page=2')
            ->assertOk()
            ->assertSee('Pertanian Berita 10');
    }

    public function test_guest_is_redirected_to_login_when_previewing_a_draft(): void
    {
        $post = Post::factory()->create(['title' => 'Draf Pratinjau Rahasia']);

        $response = $this->get(route('posts.preview', $post));

        $response->assertRedirect(route('filament.admin.auth.login'));
        $this->assertSame(0, $post->refresh()->views);
    }

    public function test_editor_can_preview_a_draft_without_publishing_or_incrementing_views(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->create(['title' => 'Draf Pratinjau Artikel']);

        $response = $this->actingAs($editor)->get(route('posts.preview', $post));

        $response->assertOk()
            ->assertSee('Draf Pratinjau Artikel')
            ->assertSee('Pratinjau Draft')
            ->assertSee('noindex', false);

        $this->assertSame('draft', $post->refresh()->status->value);
        $this->assertSame(0, $post->views);
    }

    public function test_editor_cannot_see_draft_via_public_route(): void
    {
        $post = Post::factory()->create(['title' => 'Draf Tetap Rahasia']);

        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_post_detail_shows_share_actions(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Berita Dibagikan']);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk()
            ->assertSee('https://wa.me/', false)
            ->assertSee('https://twitter.com/intent/tweet', false)
            ->assertSee('data-copy-link', false);
    }

    public function test_post_detail_shows_related_posts_from_same_category(): void
    {
        $category = Category::factory()->create(['name' => 'Lingkungan Hidup Uji']);
        $otherCategory = Category::factory()->create(['name' => 'Kehutanan Uji']);

        $post = Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Utama Terkait',
        ]);
        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Sejenis Terkait',
        ]);
        Post::factory()->published()->create([
            'category_id' => $otherCategory->id,
            'title' => 'Berita Rubrik Lain',
        ]);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk()
            ->assertSee('Berita Sejenis Terkait')
            ->assertSee('Berita Terkait')
            ->assertDontSee('Berita Rubrik Lain');
    }

    public function test_inactive_category_is_not_accessible(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_search_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get('/berita');
        }

        $this->get('/berita')->assertStatus(429);
    }

    public function test_sitemap_contains_published_content_only(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Berita Sitemap']);
        $draft = Post::factory()->create(['title' => 'Draf Sitemap']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee(route('posts.show', $published), false);
        $response->assertDontSee(route('posts.show', $draft), false);
    }

    public function test_robots_file_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee(route('sitemap'));
    }

    public function test_homepage_exposes_seo_metadata(): void
    {
        Post::factory()->published()->create(['title' => 'Berita SEO']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('og:title', false);
    }
}
