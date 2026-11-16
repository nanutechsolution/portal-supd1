<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleXMLElement;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_valid_xml_with_canonical_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', (string) $response->headers->get('Content-Type'));

        $xml = simplexml_load_string((string) $response->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame('urlset', $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getNamespaces()[''] ?? null);

        $urls = $this->sitemapUrls($xml);

        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $this->assertStringStartsWith(rtrim((string) config('app.url'), '/'), $url);
            $this->assertStringEndsNotWith('/', $url);
        }
    }

    public function test_sitemap_lists_every_public_page_exactly_once(): void
    {
        $urls = $this->sitemapUrls();

        foreach ([
            route('home'),
            route('posts.index'),
            route('bulletins.index'),
            route('galleries.index'),
            route('pages.profil'),
            route('structure.index'),
            route('contact.index'),
        ] as $expected) {
            $this->assertSame(1, $this->countUrl($urls, $expected), "URL tidak muncul tepat sekali: {$expected}");
        }

        $this->assertSame(count($urls), count(array_unique($urls)), 'Sitemap memuat URL duplikat.');
    }

    public function test_sitemap_only_contains_published_and_public_content(): void
    {
        $publishedPost = Post::factory()->published()->create();
        $draftPost = Post::factory()->create();
        $activeCategory = Category::factory()->create();
        $inactiveCategory = Category::factory()->inactive()->create();
        $publishedBulletin = Bulletin::factory()->published()->create();
        $hiddenBulletin = Bulletin::factory()->create();

        $body = (string) $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString(route('posts.show', $publishedPost), $body);
        $this->assertStringNotContainsString(route('posts.show', $draftPost), $body);

        $this->assertStringContainsString(route('categories.show', $activeCategory), $body);
        $this->assertStringNotContainsString(route('categories.show', $inactiveCategory), $body);

        $this->assertStringContainsString(route('bulletins.show', $publishedBulletin), $body);
        $this->assertStringNotContainsString(route('bulletins.show', $hiddenBulletin), $body);

        foreach (['/admin', route('search.index'), '/pratinjau', '/unduh'] as $privateUrl) {
            $this->assertStringNotContainsString($privateUrl, $body);
        }
    }

    public function test_static_pages_expose_last_modified_dates(): void
    {
        $page = Page::factory()->create(['slug' => 'tentang-supd1']);
        $gallery = GalleryItem::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $xml = simplexml_load_string((string) $this->get('/sitemap.xml')->getContent());
        $this->assertNotFalse($xml);

        $lastmods = [];

        foreach ($xml->url as $entry) {
            $lastmods[(string) $entry->loc] = (string) $entry->lastmod;
        }

        $this->assertSame($page->fresh()->updated_at?->toAtomString(), $lastmods[route('pages.profil')] ?? null);
        $this->assertSame($gallery->fresh()->updated_at?->toAtomString(), $lastmods[route('galleries.index')] ?? null);
        $this->assertSame($unit->fresh()->updated_at?->toAtomString(), $lastmods[route('structure.index')] ?? null);
    }

    /**
     * @return list<string>
     */
    private function sitemapUrls(?SimpleXMLElement $xml = null): array
    {
        $xml ??= simplexml_load_string((string) $this->get('/sitemap.xml')->getContent());

        $this->assertNotFalse($xml);

        $urls = [];

        foreach ($xml->url as $entry) {
            $urls[] = (string) $entry->loc;
        }

        return $urls;
    }

    /**
     * @param  list<string>  $urls
     */
    private function countUrl(array $urls, string $expected): int
    {
        return count(array_filter($urls, static fn (string $url): bool => $url === $expected));
    }
}
