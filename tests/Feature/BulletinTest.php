<?php

namespace Tests\Feature;

use App\Models\Bulletin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulletinTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulletin_index_shows_only_published_bulletins(): void
    {
        $published = Bulletin::factory()->published()->create(['title' => 'Buletin Terbit']);
        $draft = Bulletin::factory()->create(['title' => 'Buletin Draf']);

        $this->get(route('bulletins.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_published_bulletin_detail_is_accessible(): void
    {
        $bulletin = Bulletin::factory()->published()->create(['title' => 'Buletin Detail']);

        $this->get(route('bulletins.show', $bulletin))
            ->assertOk()
            ->assertSee('Buletin Detail')
            ->assertSee('Edisi '.$bulletin->edition_number);
    }

    public function test_draft_bulletin_detail_returns_not_found(): void
    {
        $bulletin = Bulletin::factory()->create();

        $this->get(route('bulletins.show', $bulletin))->assertNotFound();
    }

    public function test_bulletin_can_be_downloaded_and_counter_increments(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdf/edisi-01.pdf', '%PDF-1.4 contoh');

        $bulletin = Bulletin::factory()->published()->create([
            'file_path' => 'bulletins/pdf/edisi-01.pdf',
        ]);

        $response = $this->get(route('bulletins.download', $bulletin));

        $response->assertOk();
        $response->assertDownload();
        $this->assertSame(1, $bulletin->fresh()->downloads);
    }

    public function test_bulletin_pdf_can_be_previewed_inline(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdf/edisi-02.pdf', '%PDF-1.4 contoh');

        $bulletin = Bulletin::factory()->published()->create([
            'file_path' => 'bulletins/pdf/edisi-02.pdf',
        ]);

        $response = $this->get(route('bulletins.preview', $bulletin));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_draft_bulletin_cannot_be_downloaded(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bulletins/pdf/edisi-03.pdf', '%PDF-1.4 contoh');

        $bulletin = Bulletin::factory()->create([
            'file_path' => 'bulletins/pdf/edisi-03.pdf',
        ]);

        $this->get(route('bulletins.download', $bulletin))->assertNotFound();
    }

    public function test_bulletin_without_file_returns_not_found_for_preview(): void
    {
        $bulletin = Bulletin::factory()->published()->create();

        $this->get(route('bulletins.preview', $bulletin))->assertNotFound();
    }

    public function test_bulletin_index_can_be_filtered_by_year(): void
    {
        $current = Bulletin::factory()->published()->create([
            'title' => 'Buletin Tahun Ini',
            'year' => (int) now()->format('Y'),
        ]);
        $previous = Bulletin::factory()->published()->create([
            'title' => 'Buletin Tahun Lalu',
            'year' => (int) now()->subYear()->format('Y'),
        ]);

        $this->get(route('bulletins.index', ['tahun' => $current->year]))
            ->assertOk()
            ->assertSee($current->title)
            ->assertDontSee($previous->title);
    }
}
