<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Setting::flushCache();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    public function test_settings_form_hydrates_toggle_defaults(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->assertFormSet([
                'show_header_topbar' => true,
                'show_header_search' => true,
                'breaking_news_enabled' => true,
            ]);
    }

    public function test_ministry_identity_settings_have_defaults(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->assertFormSet([
                'site_ministry_line1' => 'Direktorat Jenderal Bina Pembangunan Daerah',
                'site_ministry_line2' => 'Kementerian Dalam Negeri',
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'site_ministry_line1',
            'value' => 'Direktorat Jenderal Bina Pembangunan Daerah',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'site_ministry_line2',
            'value' => 'Kementerian Dalam Negeri',
        ]);

        $this->assertSame('Direktorat Jenderal Bina Pembangunan Daerah', Setting::get('site_ministry_line1'));
        $this->assertSame('Kementerian Dalam Negeri', Setting::get('site_ministry_line2'));
    }

    public function test_ministry_identity_settings_can_be_cleared(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->set('data.site_ministry_line1', '')
            ->set('data.site_ministry_line2', '')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('', Setting::get('site_ministry_line1'));
        $this->assertSame('', Setting::get('site_ministry_line2'));
    }

    public function test_administrator_can_save_website_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'Portal Uji SUPD',
                'site_short_name' => 'SUPD Uji',
                'site_directorate' => 'Direktorat Uji',
                'site_ministry_line1' => 'Direktorat Jenderal Uji',
                'site_ministry_line2' => 'Kementerian Uji',
                'site_portal_label' => 'Portal Uji',
                'site_description' => 'Deskripsi website pengujian.',
                'site_tagline' => 'Tagline pengujian.',
                'contact_email' => 'halo@example.test',
                'contact_phone' => '021-123456',
                'contact_address' => 'Jl. Pengujian No. 1',
                'site_parent_name' => 'Instansi Induk Uji',
                'site_parent_url' => 'https://induk.example.test',
                'social_instagram' => 'https://instagram.com/uji',
                'social_facebook' => 'https://facebook.com/uji',
                'social_youtube' => 'https://youtube.com/@uji',
                'seo_title' => 'Judul SEO Uji',
                'seo_description' => 'Deskripsi SEO pengujian.',
                'theme_primary' => '#123456',
                'theme_secondary' => '#abcdef',
                'footer_copyright' => 'Uji {year}',
                'show_header_topbar' => false,
                'show_header_search' => false,
                'show_footer_logo' => true,
                'show_footer_contact' => true,
                'show_footer_socials' => true,
                'homepage_news_count' => 8,
                'posts_per_page' => 4,
                'popular_posts_count' => 2,
                'breaking_news_enabled' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Portal Uji SUPD']);
        $this->assertDatabaseHas('settings', ['key' => 'site_ministry_line1', 'value' => 'Direktorat Jenderal Uji']);
        $this->assertDatabaseHas('settings', ['key' => 'site_ministry_line2', 'value' => 'Kementerian Uji']);
        $this->assertDatabaseHas('settings', ['key' => 'theme_primary', 'value' => '#123456']);
        $this->assertDatabaseHas('settings', ['key' => 'breaking_news_enabled', 'value' => '0']);

        $this->assertSame('Portal Uji SUPD', Setting::get('site_name'));
        $this->assertSame('Direktorat Jenderal Uji', Setting::get('site_ministry_line1'));
        $this->assertSame('Kementerian Uji', Setting::get('site_ministry_line2'));
        $this->assertSame(4, Setting::int('posts_per_page'));
        $this->assertFalse(Setting::bool('breaking_news_enabled', true));
        $this->assertFalse(Setting::bool('show_header_search', true));
    }

    public function test_logo_can_be_uploaded_and_is_used_on_the_frontend(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => UploadedFile::fake()->image('logo.png', 400, 200),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = Setting::get('site_logo');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->get('/')
            ->assertOk()
            ->assertSee(Setting::mediaUrl('site_logo'), false);
    }

    public function test_replacing_the_logo_through_the_form_removes_the_old_file(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm(['site_logo' => UploadedFile::fake()->image('logo-awal.png', 400, 200)])
            ->call('save')
            ->assertHasNoFormErrors();

        $oldPath = Setting::get('site_logo');

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm(['site_logo' => [UploadedFile::fake()->image('logo-baru.png', 500, 250)]])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = Setting::get('site_logo');

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_saving_other_fields_keeps_the_existing_logo(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => UploadedFile::fake()->image('logo-tetap.png', 400, 200),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = Setting::get('site_logo');

        $this->assertNotNull($path);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_short_name' => 'Nama Lain',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($path, Setting::get('site_logo'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_clearing_a_media_setting_removes_the_stored_file(): void
    {
        Storage::disk('public')->put('settings/favicon-lama.png', 'favicon lama');
        Setting::putMany(['site_favicon' => 'settings/favicon-lama.png']);

        Setting::putMany(['site_favicon' => null]);

        $this->assertNull(Setting::mediaUrl('site_favicon'));
        Storage::disk('public')->assertMissing('settings/favicon-lama.png');
    }

    public function test_favicon_can_be_updated_and_is_linked_on_public_pages(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = Setting::get('site_favicon');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee(Setting::mediaUrl('site_favicon'), false)
            ->assertSee('type="image/png"', false);
    }

    public function test_frontend_reflects_saved_identity_contact_appearance_and_copyright(): void
    {
        Setting::putMany([
            'site_name' => 'Portal Identitas Uji',
            'site_short_name' => 'Identitas Uji',
            'site_directorate' => 'Direktorat Identitas Uji',
            'site_portal_label' => 'Label Portal Uji',
            'site_description' => 'Deskripsi tampilan pengujian.',
            'site_tagline' => 'Tagline tampilan pengujian.',
            'contact_address' => 'Jl. Tampilan No. 9',
            'contact_email' => 'tampilan@example.test',
            'contact_phone' => '021-987654',
            'site_parent_name' => 'Instansi Induk Tampilan',
            'site_parent_url' => 'https://induk-tampilan.example.test',
            'social_instagram' => 'https://instagram.com/tampilan',
            'social_facebook' => 'https://facebook.com/tampilan',
            'footer_copyright' => 'Hak Cipta {year} Uji',
            'theme_primary' => '#123456',
            'theme_secondary' => '#abcdef',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Portal Identitas Uji')
            ->assertSee('Identitas Uji')
            ->assertSee('Label Portal Uji')
            ->assertSee('Direktorat Identitas Uji')
            ->assertSee('Jl. Tampilan No. 9')
            ->assertSee('tampilan@example.test')
            ->assertSee('021-987654')
            ->assertSee('https://induk-tampilan.example.test', false)
            ->assertSee('https://instagram.com/tampilan', false)
            ->assertSee('https://facebook.com/tampilan', false)
            ->assertSee('Hak Cipta '.now()->year.' Uji')
            ->assertSee('--color-ink-900:#123456;', false)
            ->assertSee('--color-accent-400:#abcdef;', false);

        $this->get('/profil')
            ->assertOk()
            ->assertSee('Direktorat Identitas Uji')
            ->assertSee('Jl. Tampilan No. 9')
            ->assertSee('Instansi Induk Tampilan');
    }

    public function test_seo_metadata_follows_the_configuration(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'seo_title' => 'Judul SEO Kustom',
                'seo_description' => 'Deskripsi meta kustom untuk pengujian.',
                'seo_og_image' => UploadedFile::fake()->image('og.png', 1200, 630),
                'seo_social_image' => UploadedFile::fake()->image('social.png', 1200, 630),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $ogPath = Setting::get('seo_og_image');
        $socialPath = Setting::get('seo_social_image');

        $this->assertNotNull($ogPath);
        $this->assertNotNull($socialPath);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Judul SEO Kustom</title>', false)
            ->assertSee('name="description" content="Deskripsi meta kustom untuk pengujian."', false)
            ->assertSee('property="og:title" content="Judul SEO Kustom"', false)
            ->assertSee('property="og:description" content="Deskripsi meta kustom untuk pengujian."', false)
            ->assertSee('property="og:image" content="'.Setting::mediaUrl('seo_og_image').'"', false)
            ->assertSee('name="twitter:image" content="'.Setting::mediaUrl('seo_social_image').'"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_editor_cannot_save_website_settings(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);
        $this->assertFalse(ManageSettings::canAccess());

        $this->get('/admin/manage-settings')->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'site_name']);
    }

    public function test_settings_cache_is_refreshed_after_saving(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame('SUPD I', Setting::get('site_short_name'));

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_short_name' => 'Nama Baru',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nama Baru', Setting::get('site_short_name'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Nama Baru');
    }

    public function test_news_counts_and_breaking_news_setting_are_applied(): void
    {
        Post::factory()->count(5)->published()->create();

        Setting::putMany([
            'homepage_news_count' => 3,
            'posts_per_page' => 3,
            'popular_posts_count' => 2,
            'breaking_news_enabled' => '0',
        ]);

        $home = $this->get('/')->assertOk();
        $home->assertDontSee('Terkini');

        $this->assertCount(3, $home->viewData('latest'));
        $this->assertCount(2, $home->viewData('popular'));

        $index = $this->get('/berita')->assertOk();
        $this->assertSame(3, $index->viewData('posts')->perPage());
    }

    public function test_logo_upload_rejects_svg_and_tiny_images(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            ])
            ->call('save')
            ->assertHasFormErrors(['site_logo']);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => UploadedFile::fake()->image('logo-kecil.png', 10, 10),
            ])
            ->call('save')
            ->assertHasFormErrors(['site_logo']);

        $this->assertDatabaseMissing('settings', ['key' => 'site_logo']);
    }
}
