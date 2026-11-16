<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
    }

    public function test_rss_feed_is_valid_xml_with_rss20_structure(): void
    {
        Post::factory()->published()->create(['title' => 'Berita RSS Uji']);

        $response = $this->get('/rss.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/rss+xml', $response->headers->get('Content-Type') ?? '');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'Feed harus XML yang valid.');
        $this->assertSame('rss', $xml->getName());
        $this->assertSame('2.0', (string) $xml['version']);
        $this->assertNotNull($xml->channel);
        $this->assertNotNull($xml->channel->title);
        $this->assertNotNull($xml->channel->link);
        $this->assertNotNull($xml->channel->description);
    }

    public function test_rss_feed_only_contains_published_posts(): void
    {
        Post::factory()->published()->create(['title' => 'Berita RSS Publik']);
        Post::factory()->create(['title' => 'Berita RSS Draft']);
        Post::factory()->published()->create([
            'title' => 'Berita RSS Terjadwal',
            'published_at' => now()->addDay(),
        ]);

        $content = $this->get('/rss.xml')->assertOk()->getContent();

        $this->assertStringContainsString('Berita RSS Publik', $content);
        $this->assertStringNotContainsString('Berita RSS Draft', $content);
        $this->assertStringNotContainsString('Berita RSS Terjadwal', $content);
    }

    public function test_rss_feed_items_include_required_fields_and_absolute_urls(): void
    {
        Storage::disk('public')->put('posts/rss.jpg', str_repeat('x', 4096));

        $post = Post::factory()->published()->create([
            'title' => 'Berita RSS Lengkap',
            'featured_image' => 'posts/rss.jpg',
            'excerpt' => 'Ringkasan berita untuk pengujian feed.',
        ]);

        $content = $this->get('/rss.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Berita RSS Lengkap</title>', $content);
        $this->assertStringContainsString('<link>'.route('posts.show', $post).'</link>', $content);
        $this->assertStringContainsString('<pubDate>', $content);
        $this->assertStringContainsString('<dc:creator>', $content);
        $this->assertStringContainsString('<category>', $content);
        $this->assertStringContainsString('<description>', $content);
        $this->assertStringContainsString('Ringkasan berita untuk pengujian feed.', $content);
        $this->assertStringContainsString('enclosure url="', $content);
        $this->assertStringContainsString('length="4096"', $content);
        $this->assertStringContainsString('posts/rss.jpg', $content);
        $this->assertStringContainsString(route('posts.show', $post), $content);
        $this->assertStringNotContainsString('src="/', $content);
    }

    public function test_rss_feed_escapes_xml_unsafe_characters(): void
    {
        Post::factory()->published()->create([
            'title' => 'Judul & <Hebat> "Sulit"',
            'excerpt' => "Ringkasan & \x07 tanda <br> tag \"asli\"",
        ]);

        $response = $this->get('/rss.xml');

        $response->assertOk();

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'Feed harus tetap valid meski berisi karakter spesial.');
        $this->assertStringContainsString('<title>Judul &amp; &lt;Hebat&gt; &quot;Sulit&quot;</title>', $response->getContent());
        $this->assertStringNotContainsString("\x07", $response->getContent());
    }

    public function test_rss_feed_serves_cached_items(): void
    {
        Post::factory()->published()->create(['title' => 'Berita RSS Cache']);

        $first = $this->get('/rss.xml')->assertOk()->getContent();

        Post::query()->delete();

        $second = $this->get('/rss.xml')->assertOk()->getContent();

        $this->assertSame($first, $second, 'Respons kedua harus dilayani dari cache, bukan database.');
        $this->assertStringContainsString('Berita RSS Cache', $second);
    }

    public function test_feed_alias_returns_the_same_feed(): void
    {
        Post::factory()->published()->create(['title' => 'Berita RSS Alias']);

        $response = $this->get('/feed');

        $response->assertOk();
        $this->assertStringContainsString('application/rss+xml', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('Berita RSS Alias', $response->getContent());
    }

    public function test_homepage_advertises_the_rss_feed(): void
    {
        $this->get('/')->assertOk()->assertSee('application/rss+xml', false);
    }
}
