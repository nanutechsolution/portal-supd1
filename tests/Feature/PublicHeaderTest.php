<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_header_shows_ministry_identity_defaults(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Direktorat Jenderal Bina Pembangunan Daerah')
            ->assertSee('Kementerian Dalam Negeri')
            ->assertSee('Direktorat Sinkronisasi Urusan Pemerintahan Daerah I')
            ->assertSee('Portal Informasi dan Buletin');
    }

    public function test_header_uses_custom_ministry_identity_settings(): void
    {
        Setting::putMany([
            'site_ministry_line1' => 'Direktorat Jenderal Identitas Uji',
            'site_ministry_line2' => 'Kementerian Identitas Uji',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Direktorat Jenderal Identitas Uji')
            ->assertSee('Kementerian Identitas Uji')
            ->assertDontSee('Direktorat Jenderal Bina Pembangunan Daerah')
            ->assertDontSee('Kementerian Dalam Negeri');
    }

    public function test_header_omits_ministry_lines_that_are_empty(): void
    {
        Setting::putMany([
            'site_ministry_line1' => '',
            'site_ministry_line2' => '',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Direktorat Jenderal Bina Pembangunan Daerah')
            ->assertDontSee('Kementerian Dalam Negeri')
            ->assertDontSee('Kementerian Identitas Uji');
    }

    public function test_header_keeps_logo_fallback_and_accessible_identity_label(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('aria-label="Portal Informasi dan Buletin Direktorat SUPD I — Beranda"', false)
            ->assertSee('aria-label="Portal SUPD I"', false);
    }

    public function test_header_uses_uploaded_logo_with_alt_text(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/logo-header.png', 'logo');

        Setting::putMany([
            'site_logo' => 'settings/logo-header.png',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('alt="Logo SUPD I"', false)
            ->assertSee(Setting::mediaUrl('site_logo'), false);
    }

    public function test_header_orders_identity_lines_under_the_site_short_name(): void
    {
        $html = (string) $this->get('/')->getContent();
        $header = Str::between($html, '<header', '</header>');

        $shortNamePosition = strpos($header, 'SUPD I');
        $directoratePosition = strpos($header, 'Direktorat Sinkronisasi Urusan Pemerintahan Daerah I');
        $ministryLine1Position = strpos($header, 'Direktorat Jenderal Bina Pembangunan Daerah');
        $ministryLine2Position = strpos($header, 'Kementerian Dalam Negeri');

        $this->assertNotFalse($shortNamePosition);
        $this->assertNotFalse($directoratePosition);
        $this->assertNotFalse($ministryLine1Position);
        $this->assertNotFalse($ministryLine2Position);

        $this->assertTrue($shortNamePosition < $directoratePosition);
        $this->assertTrue($directoratePosition < $ministryLine1Position);
        $this->assertTrue($ministryLine1Position < $ministryLine2Position);

        $this->assertStringContainsString('text-sm font-bold leading-tight text-ink-600', $header);
        $this->assertStringContainsString('text-sm font-bold leading-tight text-ink-800', $header);
        $this->assertStringContainsString('eyebrow mt-1 block text-accent-600', $header);
        $this->assertStringNotContainsString('items-baseline', $header);
    }

    public function test_mobile_menu_toggle_is_accessible_and_large_enough(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-mobile-toggle', false)
            ->assertSee('aria-controls="mobile-menu"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('h-11 w-11', false);
    }
}
