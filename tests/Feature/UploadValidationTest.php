<?php

namespace Tests\Feature;

use App\Filament\Resources\Bulletins\Pages\CreateBulletin;
use App\Filament\Resources\GalleryItems\Pages\CreateGalleryItem;
use App\Models\Bulletin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulletin_accepts_a_valid_pdf_upload(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateBulletin::class)
            ->fillForm([
                'title' => 'Buletin Uji PDF',
                'edition_number' => '07',
                'year' => (int) now()->format('Y'),
                'is_published' => true,
                'file_path' => UploadedFile::fake()->create('buletin.pdf', 512, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $bulletin = Bulletin::query()->where('title', 'Buletin Uji PDF')->first();

        $this->assertNotNull($bulletin);
        $this->assertNotNull($bulletin->file_path);
        Storage::disk('public')->assertExists($bulletin->file_path);
    }

    public function test_bulletin_rejects_a_non_pdf_upload(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateBulletin::class)
            ->fillForm([
                'title' => 'Buletin Dokumen Salah',
                'edition_number' => '08',
                'year' => (int) now()->format('Y'),
                'file_path' => UploadedFile::fake()->create('dokumen.docx', 64, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->call('create')
            ->assertHasFormErrors(['file_path']);

        $this->assertDatabaseMissing('bulletins', ['title' => 'Buletin Dokumen Salah']);
    }

    public function test_gallery_requires_an_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateGalleryItem::class)
            ->fillForm([
                'title' => 'Foto Tanpa Gambar',
                'documented_at' => now()->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasFormErrors(['image' => 'required']);
    }

    public function test_gallery_accepts_a_valid_image_upload(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateGalleryItem::class)
            ->fillForm([
                'title' => 'Foto Kegiatan Uji',
                'image' => UploadedFile::fake()->image('kegiatan.jpg', 1200, 800),
                'documented_at' => now()->format('Y-m-d'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('gallery_items', ['title' => 'Foto Kegiatan Uji']);
    }
}
