<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
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

    public function test_empty_query_shows_search_intro_and_suggestions(): void
    {
        Category::factory()->create(['name' => 'Rubrik Saran Uji']);

        $this->get(route('search.index'))
            ->assertOk()
            ->assertSee('Mulai dengan kata kunci')
            ->assertSee('Saran Pencarian')
            ->assertSee('Rubrik Rubrik Saran Uji')
            ->assertSee('Arsip Berita');
    }

    public function test_search_finds_posts_and_bulletins_in_their_groups(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Sinkronisasi Global Uji Coba']);

        Bulletin::factory()->published()->create([
            'title' => 'Buletin Global Uji Coba',
            'edition_number' => '17',
        ]);

        $this->get(route('search.index', ['q' => 'global uji']))
            ->assertOk()
            ->assertSee('id="hasil-berita"', false)
            ->assertSee($post->title)
            ->assertSee('id="hasil-buletin"', false)
            ->assertSee('Buletin Global Uji Coba')
            ->assertSee('Edisi 17');
    }

    public function test_search_finds_static_portal_pages(): void
    {
        $this->get(route('search.index', ['q' => 'struktur']))
            ->assertOk()
            ->assertSee('Struktur Organisasi')
            ->assertSee('Halaman');
    }

    public function test_search_matches_editable_profile_page_content(): void
    {
        Page::factory()->create([
            'slug' => 'tentang-supd1',
            'title' => 'Tentang Direktorat',
            'content' => '<p>Pelayanan publik yang transparan dan akuntabel.</p>',
        ]);

        $this->get(route('search.index', ['q' => 'akuntabel']))
            ->assertOk()
            ->assertSee('Tentang Direktorat')
            ->assertSee('/profil#tentang-heading', false);
    }

    public function test_search_excludes_drafts_and_scheduled_posts(): void
    {
        Post::factory()->create(['title' => 'Draf Pencarian Global Rahasia']);

        Post::factory()->published()->create([
            'title' => 'Terjadwal Pencarian Global Rahasia',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('search.index', ['q' => 'rahasia']))
            ->assertOk()
            ->assertSee('Tidak ada hasil')
            ->assertDontSee('Draf Pencarian Global Rahasia')
            ->assertDontSee('Terjadwal Pencarian Global Rahasia');
    }

    public function test_no_results_shows_a_friendly_empty_state_and_suggestions(): void
    {
        $this->get(route('search.index', ['q' => 'zzzz-tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada hasil')
            ->assertSee('Saran Pencarian')
            ->assertSee('Cari di berita saja');
    }

    public function test_search_page_is_not_indexed_by_search_engines(): void
    {
        $this->get(route('search.index', ['q' => 'berita']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_header_search_form_targets_the_global_search_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('search.index').'"', false)
            ->assertSee('Cari berita, buletin, atau halaman', false);
    }

    public function test_search_result_shows_total_count_and_link_to_all_news_results(): void
    {
        Post::factory()->published()->count(12)->sequence(
            fn ($sequence) => [
                'title' => 'Berita Banyak Hasil '.$sequence->index,
                'published_at' => now()->subMinutes($sequence->index + 1),
            ],
        )->create();

        $this->get(route('search.index', ['q' => 'banyak hasil']))
            ->assertOk()
            ->assertSee('12 berita cocok')
            ->assertSee('Lihat semua hasil berita');
    }
}
