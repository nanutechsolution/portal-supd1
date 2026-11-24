<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Berita Baru dari Panel',
                'excerpt' => 'Ringkasan berita baru.',
                'content' => '<p>Isi berita baru.</p>',
                'category_id' => $category->id,
                'status' => PostStatus::Published->value,
                'published_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('title', 'Berita Baru dari Panel')->first();

        $this->assertNotNull($post);
        $this->assertSame('berita-baru-dari-panel', $post->slug);
        $this->assertSame($admin->id, $post->user_id);
        $this->assertSame(PostStatus::Published, $post->status);
    }

    public function test_administrator_can_edit_a_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['title' => 'Judul Awal']);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => 'Judul Setelah Diedit',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Judul Setelah Diedit', $post->fresh()->title);
    }

    public function test_administrator_can_mark_a_post_as_breaking_news(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['title' => 'Berita Breaking']);

        Livewire::actingAs($admin)
            ->test(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'is_breaking' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($post->fresh()->is_breaking);
    }

    public function test_slug_is_generated_uniquely_from_title(): void
    {
        $first = Post::factory()->create(['title' => 'Berita Sama']);
        $second = Post::factory()->create(['title' => 'Berita Sama']);

        $this->assertSame('berita-sama', $first->slug);
        $this->assertSame('berita-sama-2', $second->slug);
    }

    public function test_administrator_can_create_a_category_with_automatic_slug(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Substansi Uji Coba',
                'description' => 'Kategori untuk pengujian.',
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Substansi Uji Coba',
            'slug' => 'substansi-uji-coba',
        ]);
    }

    public function test_draft_post_created_in_panel_is_not_visible_publicly(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePost::class)
            ->fillForm([
                'title' => 'Draf dari Panel',
                'content' => '<p>Draf.</p>',
                'category_id' => $category->id,
                'status' => PostStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/berita')->assertDontSee('Draf dari Panel');
    }
}
