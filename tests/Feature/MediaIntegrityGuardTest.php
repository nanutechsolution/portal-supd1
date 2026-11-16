<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Bulletins\Pages\EditBulletin;
use App\Filament\Resources\GalleryItems\Pages\EditGalleryItem;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Bulletin;
use App\Models\GalleryItem;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\MediaIntegrityGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class MediaIntegrityGuardTest extends TestCase
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

    public function test_settings_media_loss_without_explicit_removal_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $fields = Setting::mediaKeys();

        foreach ($fields as $field) {
            $this->seedSettingMedia($field, "settings/{$field}-lama.png");
        }

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm(array_fill_keys($fields, null))
            ->call('save')
            ->assertHasFormErrors($fields);

        $this->assertSettingMediaIntact($fields);
    }

    public function test_settings_media_can_be_removed_through_the_explicit_option(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (Setting::mediaKeys() as $field) {
            $path = "settings/{$field}-lama.png";
            $this->seedSettingMedia($field, $path);

            Livewire::actingAs($admin)
                ->test(ManageSettings::class)
                ->fillForm([MediaIntegrityGuard::removalFlag($field) => true])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertNull(Setting::rawValues()[$field] ?? null);
            Storage::disk('public')->assertMissing($path);

            Setting::putMany([$field => null]);
            Storage::disk('public')->delete($path);
            Setting::flushCache();
        }
    }

    public function test_settings_removal_flag_does_not_block_a_later_replacement(): void
    {
        $admin = User::factory()->admin()->create();
        $path = 'settings/site_logo-lama.png';
        $this->seedSettingMedia('site_logo', $path);

        $component = Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([MediaIntegrityGuard::removalFlag('site_logo') => true])
            ->call('save');

        $component->assertHasNoFormErrors();

        $component
            ->fillForm(['site_logo' => [UploadedFile::fake()->image('logo-baru.png', 400, 200)]])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = Setting::rawValues()['site_logo'] ?? null;

        $this->assertIsString($newPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_settings_media_can_be_replaced(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (Setting::mediaKeys() as $field) {
            $oldPath = "settings/{$field}-lama.png";
            $this->seedSettingMedia($field, $oldPath);

            Livewire::actingAs($admin)
                ->test(ManageSettings::class)
                ->fillForm([$field => [$this->settingsUpload($field)]])
                ->call('save')
                ->assertHasNoFormErrors();

            $newPath = Setting::rawValues()[$field] ?? null;

            $this->assertIsString($newPath);
            $this->assertNotSame($oldPath, $newPath);
            Storage::disk('public')->assertMissing($oldPath);
            Storage::disk('public')->assertExists($newPath);

            Setting::putMany([$field => null]);
            Setting::flushCache();
        }
    }

    public function test_settings_database_failure_keeps_the_old_media(): void
    {
        $admin = User::factory()->admin()->create();
        $oldPath = 'settings/site_logo-lama.png';
        $this->seedSettingMedia('site_logo', $oldPath);

        Event::listen(
            'eloquent.saving: '.Setting::class,
            fn (): never => throw new RuntimeException('Simulated database failure'),
        );

        try {
            Livewire::actingAs($admin)
                ->test(ManageSettings::class)
                ->fillForm(['site_logo' => [UploadedFile::fake()->image('logo-baru.png', 400, 200)]])
                ->call('save');

            $this->fail('The simulated database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            Event::forget('eloquent.saving: '.Setting::class);
        }

        $this->assertSame($oldPath, Setting::rawValues()['site_logo'] ?? null);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_settings_media_survives_validation_failure(): void
    {
        $admin = User::factory()->admin()->create();
        $oldPath = 'settings/favicon-lama.png';
        $this->seedSettingMedia('site_favicon', $oldPath);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_favicon' => UploadedFile::fake()->create('favicon.svg', 10, 'image/svg+xml'),
            ])
            ->call('save')
            ->assertHasFormErrors(['site_favicon']);

        $this->assertSame($oldPath, Setting::rawValues()['site_favicon'] ?? null);
        Storage::disk('public')->assertExists($oldPath);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_favicon' => UploadedFile::fake()->image('terlalu-kecil.png', 10, 10),
            ])
            ->call('save')
            ->assertHasFormErrors(['site_favicon']);

        $this->assertSame($oldPath, Setting::rawValues()['site_favicon'] ?? null);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_saving_unrelated_settings_keeps_every_media_file(): void
    {
        $admin = User::factory()->admin()->create();
        $fields = Setting::mediaKeys();

        foreach ($fields as $field) {
            $this->seedSettingMedia($field, "settings/{$field}-lama.png");
        }

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm(['site_short_name' => 'Nama Portal Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nama Portal Baru', Setting::get('site_short_name'));
        $this->assertSettingMediaIntact($fields);
    }

    public function test_settings_reject_removal_and_replacement_at_the_same_time(): void
    {
        $admin = User::factory()->admin()->create();
        $oldPath = 'settings/site_logo-lama.png';
        $this->seedSettingMedia('site_logo', $oldPath);

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_logo' => [UploadedFile::fake()->image('logo-baru.png', 400, 200)],
                MediaIntegrityGuard::removalFlag('site_logo') => true,
            ])
            ->call('save')
            ->assertHasFormErrors(['site_logo']);

        $this->assertSame($oldPath, Setting::rawValues()['site_logo'] ?? null);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_post_featured_image_is_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $post = $this->postWithImage();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['featured_image' => null])
            ->call('save')
            ->assertHasFormErrors(['featured_image']);

        $this->assertSame('posts/lama.jpg', $post->fresh()->featured_image);
        Storage::disk('public')->assertExists('posts/lama.jpg');
    }

    public function test_post_featured_image_can_be_removed_explicitly(): void
    {
        $admin = User::factory()->admin()->create();
        $post = $this->postWithImage();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([MediaIntegrityGuard::removalFlag('featured_image') => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($post->fresh()->featured_image);
        Storage::disk('public')->assertMissing('posts/lama.jpg');
    }

    public function test_post_featured_image_can_be_replaced_and_old_file_is_removed_after_save(): void
    {
        $admin = User::factory()->admin()->create();
        $post = $this->postWithImage();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'featured_image' => [UploadedFile::fake()->image('pengganti.jpg', 1200, 800)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $post->fresh()->featured_image;

        $this->assertIsString($newPath);
        $this->assertNotSame('posts/lama.jpg', $newPath);
        Storage::disk('public')->assertMissing('posts/lama.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_post_featured_image_survives_validation_failure(): void
    {
        $admin = User::factory()->admin()->create();
        $post = $this->postWithImage();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'featured_image' => UploadedFile::fake()->create('gambar.svg', 10, 'image/svg+xml'),
            ])
            ->call('save')
            ->assertHasFormErrors(['featured_image']);

        $this->assertSame('posts/lama.jpg', $post->fresh()->featured_image);
        Storage::disk('public')->assertExists('posts/lama.jpg');
    }

    public function test_saving_unrelated_post_fields_keeps_the_featured_image(): void
    {
        $admin = User::factory()->admin()->create();
        $post = $this->postWithImage();

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['title' => 'Judul Baru Tanpa Menyentuh Gambar'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Baru Tanpa Menyentuh Gambar', $post->fresh()->title);
        $this->assertSame('posts/lama.jpg', $post->fresh()->featured_image);
        Storage::disk('public')->assertExists('posts/lama.jpg');
    }

    public function test_bulletin_cover_is_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm(['cover' => null])
            ->call('save')
            ->assertHasFormErrors(['cover']);

        $this->assertSame('bulletins/covers/lama.jpg', $bulletin->fresh()->cover);
        Storage::disk('public')->assertExists('bulletins/covers/lama.jpg');
    }

    public function test_bulletin_cover_can_be_removed_explicitly(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm([MediaIntegrityGuard::removalFlag('cover') => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($bulletin->fresh()->cover);
        Storage::disk('public')->assertMissing('bulletins/covers/lama.jpg');
        Storage::disk('public')->assertExists('bulletins/pdf/lama.pdf');
    }

    public function test_bulletin_cover_can_be_replaced(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm([
                'cover' => [UploadedFile::fake()->image('cover-baru.jpg', 1200, 800)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $bulletin->fresh()->cover;

        $this->assertIsString($newPath);
        $this->assertNotSame('bulletins/covers/lama.jpg', $newPath);
        Storage::disk('public')->assertMissing('bulletins/covers/lama.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_bulletin_pdf_is_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm(['file_path' => null])
            ->call('save')
            ->assertHasFormErrors(['file_path']);

        $this->assertSame('bulletins/pdf/lama.pdf', $bulletin->fresh()->file_path);
        Storage::disk('public')->assertExists('bulletins/pdf/lama.pdf');
    }

    public function test_bulletin_pdf_can_be_removed_explicitly(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm([MediaIntegrityGuard::removalFlag('file_path') => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($bulletin->fresh()->file_path);
        Storage::disk('public')->assertMissing('bulletins/pdf/lama.pdf');
        Storage::disk('public')->assertExists('bulletins/covers/lama.jpg');
    }

    public function test_bulletin_pdf_can_be_replaced(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm([
                'file_path' => [UploadedFile::fake()->create('edisi-baru.pdf', 512, 'application/pdf')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $bulletin->fresh()->file_path;

        $this->assertIsString($newPath);
        $this->assertNotSame('bulletins/pdf/lama.pdf', $newPath);
        Storage::disk('public')->assertMissing('bulletins/pdf/lama.pdf');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_bulletin_files_survive_validation_failure(): void
    {
        $admin = User::factory()->admin()->create();
        $bulletin = $this->bulletinWithFiles();

        Livewire::actingAs($admin)
            ->test(EditBulletin::class, ['record' => $bulletin->getRouteKey()])
            ->fillForm([
                'file_path' => UploadedFile::fake()->create('dokumen.docx', 64, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->call('save')
            ->assertHasFormErrors(['file_path']);

        $this->assertSame('bulletins/covers/lama.jpg', $bulletin->fresh()->cover);
        $this->assertSame('bulletins/pdf/lama.pdf', $bulletin->fresh()->file_path);
        Storage::disk('public')->assertExists('bulletins/covers/lama.jpg');
        Storage::disk('public')->assertExists('bulletins/pdf/lama.pdf');
    }

    public function test_gallery_image_loss_without_replacement_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->galleryItemWithImage();

        Livewire::actingAs($admin)
            ->test(EditGalleryItem::class, ['record' => $item->getRouteKey()])
            ->fillForm(['image' => null])
            ->call('save')
            ->assertHasFormErrors(['image']);

        $this->assertSame('gallery/lama.jpg', $item->fresh()->image);
        Storage::disk('public')->assertExists('gallery/lama.jpg');
    }

    public function test_gallery_image_can_be_replaced(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->galleryItemWithImage();

        Livewire::actingAs($admin)
            ->test(EditGalleryItem::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'image' => [UploadedFile::fake()->image('kegiatan-baru.jpg', 1200, 800)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $item->fresh()->image;

        $this->assertIsString($newPath);
        $this->assertNotSame('gallery/lama.jpg', $newPath);
        Storage::disk('public')->assertMissing('gallery/lama.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_gallery_image_is_removed_when_the_item_is_deleted_explicitly(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->galleryItemWithImage();

        Livewire::actingAs($admin)
            ->test(EditGalleryItem::class, ['record' => $item->getRouteKey()])
            ->callAction('delete')
            ->callMountedAction();

        $this->assertDatabaseMissing('gallery_items', ['id' => $item->id]);
        Storage::disk('public')->assertMissing('gallery/lama.jpg');
    }

    public function test_saving_unrelated_gallery_fields_keeps_the_image(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->galleryItemWithImage();

        Livewire::actingAs($admin)
            ->test(EditGalleryItem::class, ['record' => $item->getRouteKey()])
            ->fillForm(['title' => 'Judul Galeri Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Galeri Baru', $item->fresh()->title);
        $this->assertSame('gallery/lama.jpg', $item->fresh()->image);
        Storage::disk('public')->assertExists('gallery/lama.jpg');
    }

    private function seedSettingMedia(string $field, string $path): void
    {
        Storage::disk('public')->put($path, 'media lama');
        Setting::putMany([$field => $path]);
        Setting::flushCache();
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function assertSettingMediaIntact(array $fields): void
    {
        foreach ($fields as $field) {
            $path = "settings/{$field}-lama.png";

            $this->assertSame($path, Setting::rawValues()[$field] ?? null);
            Storage::disk('public')->assertExists($path);
        }
    }

    private function settingsUpload(string $field): UploadedFile
    {
        return match ($field) {
            'site_favicon' => UploadedFile::fake()->image('favicon-baru.png', 256, 256),
            'seo_og_image', 'seo_social_image' => UploadedFile::fake()->image("{$field}-baru.png", 1200, 630),
            default => UploadedFile::fake()->image("{$field}-baru.png", 400, 200),
        };
    }

    private function postWithImage(): Post
    {
        Storage::disk('public')->put('posts/lama.jpg', 'gambar lama');

        return Post::factory()->create([
            'featured_image' => 'posts/lama.jpg',
        ]);
    }

    private function bulletinWithFiles(): Bulletin
    {
        Storage::disk('public')->put('bulletins/covers/lama.jpg', 'cover lama');
        Storage::disk('public')->put('bulletins/pdf/lama.pdf', '%PDF-1.4 lama');

        return Bulletin::factory()->create([
            'cover' => 'bulletins/covers/lama.jpg',
            'file_path' => 'bulletins/pdf/lama.pdf',
        ]);
    }

    private function galleryItemWithImage(): GalleryItem
    {
        Storage::disk('public')->put('gallery/lama.jpg', 'foto lama');

        return GalleryItem::factory()->create([
            'image' => 'gallery/lama.jpg',
        ]);
    }
}
