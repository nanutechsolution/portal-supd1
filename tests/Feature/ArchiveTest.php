<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('id');
        Setting::flushCache();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    private function makePost(string $title, string $publishedAt): Post
    {
        return Post::factory()->published()->create([
            'title' => $title,
            'published_at' => $publishedAt,
        ]);
    }

    public function test_archive_lists_published_posts_only(): void
    {
        $published = $this->makePost('Berita Arsip Terbit', '2026-11-10 09:00:00');
        $this->makePost('Berita Arsip Draf', '2026-11-11 09:00:00')->update(['status' => 'draft']);
        Post::factory()->published()->create([
            'title' => 'Berita Arsip Terjadwal',
            'published_at' => now()->addDays(10),
        ]);

        $this->get('/arsip-berita')
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Berita Arsip Draf')
            ->assertDontSee('Berita Arsip Terjadwal');
    }

    public function test_archive_groups_counts_by_year_and_month(): void
    {
        $this->makePost('Berita 2025 Genap Satu', '2025-01-05 09:00:00');
        $this->makePost('Berita 2025 Genap Dua', '2025-01-20 09:00:00');
        $this->makePost('Berita 2025 Maret', '2025-03-11 09:00:00');
        $this->makePost('Berita 2026 November Satu', '2026-11-01 09:00:00');

        $response = $this->get('/arsip-berita');

        $response->assertOk()
            ->assertSee('2025')
            ->assertSee('2026')
            ->assertSee('Januari')
            ->assertSee('Maret')
            ->assertSee('November')
            ->assertSee('3 berita')
            ->assertSee('1 berita');
    }

    public function test_archive_filters_by_year(): void
    {
        $this->makePost('Berita 2025 Tahun Lama', '2025-06-10 09:00:00');
        $this->makePost('Berita 2026 Tahun Baru', '2026-06-10 09:00:00');

        $this->get('/arsip-berita?tahun=2025')
            ->assertOk()
            ->assertSee('Berita 2025 Tahun Lama')
            ->assertDontSee('Berita 2026 Tahun Baru');
    }

    public function test_archive_filters_by_year_and_month(): void
    {
        $this->makePost('Berita November 2026 A', '2026-11-03 09:00:00');
        $this->makePost('Berita Oktober 2026 A', '2026-10-03 09:00:00');

        $this->get('/arsip-berita?tahun=2026&bulan=11')
            ->assertOk()
            ->assertSee('Berita November 2026 A')
            ->assertDontSee('Berita Oktober 2026 A');
    }

    public function test_archive_month_without_news_shows_empty_state(): void
    {
        $this->makePost('Berita 2026 Agustus', '2026-08-17 09:00:00');

        $this->get('/arsip-berita?tahun=2025&bulan=7')
            ->assertOk()
            ->assertSee('Tidak ada berita pada periode ini')
            ->assertSee('Tampilkan semua arsip');
    }

    public function test_archive_pagination_preserves_year_and_month(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Post::factory()->published()->create([
                'title' => "Arsip November {$i}",
                'published_at' => '2026-11-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).' 09:00:00',
            ]);
        }

        $firstPage = $this->get('/arsip-berita?tahun=2026&bulan=11');

        $firstPage->assertOk()->assertSee('page=2', false);
        $this->assertStringContainsString('tahun=2026', $firstPage->getContent());

        $this->get('/arsip-berita?tahun=2026&bulan=11&page=2')
            ->assertOk()
            ->assertSee('Arsip November');
    }

    public function test_archive_seo_title_description_and_canonical(): void
    {
        $this->makePost('Berita November 2026', '2026-11-05 09:00:00');

        $response = $this->get('/arsip-berita?tahun=2026&bulan=11');

        $response->assertOk()
            ->assertSee('Arsip Berita — November 2026', false)
            ->assertSee('<link rel="canonical" href="'.e(route('archive.index', ['tahun' => 2026, 'bulan' => 11])).'"', false)
            ->assertSee('<meta name="description"', false);

        // Arsip tanpa filter menggunakan kanonik dasar yang bersih.
        $all = $this->get('/arsip-berita')->assertOk();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('archive.index').'"', $all->getContent());
    }

    public function test_archive_does_not_increment_views(): void
    {
        $post = $this->makePost('Berita Arsip Hitung', '2026-11-05 09:00:00');

        $this->get('/arsip-berita')->assertOk();

        $this->assertSame(0, $post->refresh()->views);
    }

    public function test_archive_link_is_easy_to_find_from_berita_page_and_footer(): void
    {
        $this->get('/berita')->assertOk()->assertSee('/arsip-berita', false);
        $this->get('/')->assertOk()->assertSee('/arsip-berita', false);
    }

    public function test_archive_filters_by_category(): void
    {
        $category = Category::factory()->create(['name' => 'Rubrik Arsip Uji', 'slug' => 'rubrik-arsip-uji']);
        $other = Category::factory()->create(['name' => 'Rubrik Lain Uji', 'slug' => 'rubrik-lain-uji']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Berita Rubrik Arsip',
            'published_at' => '2026-11-04 09:00:00',
        ]);
        Post::factory()->published()->create([
            'category_id' => $other->id,
            'title' => 'Berita Rubrik Lain',
            'published_at' => '2026-11-05 09:00:00',
        ]);

        $response = $this->get('/arsip-berita?kategori=rubrik-arsip-uji');

        $response->assertOk()
            ->assertSee('Berita Rubrik Arsip')
            ->assertDontSee('Berita Rubrik Lain')
            ->assertSee('Arsip Berita — Rubrik Rubrik Arsip Uji', false)
            ->assertSee('Rubrik:')
            ->assertSee('kategori=rubrik-arsip-uji', false);

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.e(route('archive.index', ['kategori' => 'rubrik-arsip-uji'])).'"',
            $response->getContent(),
        );
    }

    public function test_archive_filters_by_author(): void
    {
        $first = User::factory()->create(['name' => 'Penulis Pertama Arsip']);
        $second = User::factory()->create(['name' => 'Penulis Kedua Arsip']);

        Post::factory()->published()->create([
            'user_id' => $first->id,
            'title' => 'Berita Penulis Pertama',
            'published_at' => '2026-11-04 09:00:00',
        ]);
        Post::factory()->published()->create([
            'user_id' => $second->id,
            'title' => 'Berita Penulis Kedua',
            'published_at' => '2026-11-05 09:00:00',
        ]);

        $this->get('/arsip-berita?penulis='.$first->id)
            ->assertOk()
            ->assertSee('Berita Penulis Pertama')
            ->assertDontSee('Berita Penulis Kedua')
            ->assertSee('Arsip Berita — Penulis Penulis Pertama Arsip', false)
            ->assertSee('Penulis:');
    }

    public function test_archive_combines_category_with_year_and_month(): void
    {
        $category = Category::factory()->create(['slug' => 'rubrik-kombinasi']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Kombinasi November',
            'published_at' => '2026-11-04 09:00:00',
        ]);
        Post::factory()->published()->create([
            'category_id' => $category->id,
            'title' => 'Kombinasi Oktober',
            'published_at' => '2026-10-04 09:00:00',
        ]);

        $this->get('/arsip-berita?kategori=rubrik-kombinasi&tahun=2026&bulan=11')
            ->assertOk()
            ->assertSee('Kombinasi November')
            ->assertDontSee('Kombinasi Oktober');
    }

    public function test_archive_sidebars_list_categories_and_authors(): void
    {
        $category = Category::factory()->create(['name' => 'Rubrik Sidebar Uji']);
        $author = User::factory()->create(['name' => 'Penulis Sidebar Uji']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'user_id' => $author->id,
            'published_at' => '2026-11-04 09:00:00',
        ]);

        $this->get('/arsip-berita')
            ->assertOk()
            ->assertSee('Rubrik Sidebar Uji')
            ->assertSee('Penulis Sidebar Uji')
            ->assertSee('kategori='.$category->slug, false)
            ->assertSee('penulis='.$author->id, false);
    }

    public function test_archive_period_counts_follow_the_active_category_filter(): void
    {
        $category = Category::factory()->create(['slug' => 'rubrik-hitung']);
        $other = Category::factory()->create(['slug' => 'rubrik-hitung-lain']);

        Post::factory()->published()->create([
            'category_id' => $category->id,
            'published_at' => '2025-01-04 09:00:00',
        ]);
        Post::factory()->published()->create([
            'category_id' => $other->id,
            'published_at' => '2026-11-04 09:00:00',
        ]);

        $this->get('/arsip-berita?kategori=rubrik-hitung')
            ->assertOk()
            ->assertSee('tahun=2025', false)
            ->assertDontSee('tahun=2026', false);
    }

    public function test_archive_rejects_unknown_or_inactive_category_and_unknown_author(): void
    {
        $inactive = Category::factory()->create(['slug' => 'rubrik-nonaktif', 'is_active' => false]);

        $this->get('/arsip-berita?kategori=tidak-ada')->assertNotFound();
        $this->get('/arsip-berita?kategori='.$inactive->slug)->assertNotFound();
        $this->get('/arsip-berita?penulis=999999')->assertNotFound();
    }
}
