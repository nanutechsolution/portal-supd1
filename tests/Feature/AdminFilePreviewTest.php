<?php

namespace Tests\Feature;

use App\Support\AdminFilePreview;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFilePreviewTest extends TestCase
{
    public function test_same_origin_url_uses_configured_disk_path_not_the_app_host(): void
    {
        config(['filesystems.disks.public.url' => 'https://portal.example.test/storage']);

        $this->assertSame('/storage/posts/a.jpg', AdminFilePreview::sameOriginUrl('posts/a.jpg'));
        $this->assertSame('/storage/posts/dir/a%20b.jpg', AdminFilePreview::sameOriginUrl('posts/dir/a b.jpg'));
    }

    public function test_same_origin_url_follows_a_subpath_install(): void
    {
        config(['filesystems.disks.public.url' => 'https://example.test/portal/storage']);

        $this->assertSame('/portal/storage/posts/a.jpg', AdminFilePreview::sameOriginUrl('posts/a.jpg'));
    }

    public function test_same_origin_url_falls_back_to_storage_when_not_configured(): void
    {
        config(['filesystems.disks.public.url' => null]);

        $this->assertSame('/storage/posts/a.jpg', AdminFilePreview::sameOriginUrl('posts/a.jpg'));
    }

    public function test_uploaded_file_metadata_matches_filament_shape_with_relative_url(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('posts/gambar.jpg', str_repeat('x', 1204));

        $component = FileUpload::make('featured_image')->disk('public');

        $metadata = AdminFilePreview::uploadedFile($component, 'posts/gambar.jpg', 'gambar.jpg');

        $this->assertNotNull($metadata);
        $this->assertSame('gambar.jpg', $metadata['name']);
        $this->assertSame(1204, $metadata['size']);
        $this->assertStringStartsWith('/storage/posts/', (string) $metadata['url']);
        $this->assertStringNotContainsString('://', (string) $metadata['url']);
    }

    public function test_uploaded_file_returns_null_when_file_is_missing(): void
    {
        Storage::fake('public');

        $component = FileUpload::make('featured_image')->disk('public');

        $this->assertNull(AdminFilePreview::uploadedFile($component, 'posts/hilang.jpg', 'hilang.jpg'));
    }
}
