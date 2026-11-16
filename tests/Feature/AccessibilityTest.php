<?php

namespace Tests\Feature;

use App\Models\Category;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_skip_link_is_the_first_focusable_element_and_targets_main_content(): void
    {
        $xpath = new DOMXPath($this->homeDom());

        $skipLink = $xpath->query('//body//a[@href="#konten"]')->item(0);

        $this->assertNotNull($skipLink, 'Skip link tidak ditemukan.');
        $this->assertSame('Lewati ke konten', $this->normalizeText($skipLink->textContent));
        $this->assertStringContainsString('sr-only', $skipLink->getAttribute('class'));
        $this->assertStringContainsString('focus:not-sr-only', $skipLink->getAttribute('class'));

        $firstAnchor = $xpath->query('//body//a')->item(0);

        $this->assertSame($skipLink, $firstAnchor, 'Skip link harus menjadi tautan pertama di dalam body.');

        $main = $xpath->query('//main[@id="konten"]')->item(0);

        $this->assertNotNull($main, 'Elemen main#konten tidak ditemukan.');
        $this->assertSame('-1', $main->getAttribute('tabindex'), 'main#konten harus dapat menerima fokus.');
        $this->assertStringContainsString('scroll-mt-28', $main->getAttribute('class'), 'Target skip link perlu offset terhadap header sticky.');
    }

    public function test_rubric_links_expose_the_full_category_name_in_title(): void
    {
        $longName = 'Substansi Koordinasi Kewilayahan dan Perubahan Iklim';
        Category::factory()->create(['name' => $longName]);

        $xpath = new DOMXPath($this->homeDom());

        foreach ([
            'strip rubrik' => '//*[@data-rubrik-scroll]//a[contains(@href, "/kategori/")]',
            'menu seluler' => '//*[@id="mobile-menu"]//a[contains(@href, "/kategori/")]',
            'footer' => '//footer//a[contains(@href, "/kategori/")]',
        ] as $label => $query) {
            $links = $xpath->query($query);

            $this->assertGreaterThan(0, $links->length, "Tautan rubrik tidak ditemukan pada {$label}.");

            $titles = [];

            foreach ($links as $link) {
                $name = $this->normalizeText($link->textContent);
                $titles[] = $link->getAttribute('title');

                $this->assertSame($name, $link->getAttribute('title'), "Atribut title tidak sesuai nama rubrik pada {$label}.");
            }

            $this->assertContains($longName, $titles, "Nama rubrik panjang tidak terekspos pada {$label}.");
        }
    }

    private function homeDom(): DOMDocument
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML((string) $this->get('/')->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $dom;
    }

    private function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
