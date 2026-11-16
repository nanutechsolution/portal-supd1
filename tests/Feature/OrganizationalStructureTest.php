<?php

namespace Tests\Feature;

use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Support\MediaIntegrityGuard;
use Database\Seeders\OrganizationalUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationalStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_structure_page_shows_empty_state_without_published_units(): void
    {
        OrganizationalUnit::factory()->unpublished()->create(['name' => 'Unit Belum Terbit']);

        $this->get(route('structure.index'))
            ->assertOk()
            ->assertSee('Struktur Organisasi')
            ->assertSee('Struktur organisasi belum tersedia')
            ->assertDontSee('Unit Belum Terbit');
    }

    public function test_public_structure_page_shows_only_published_and_active_hierarchy(): void
    {
        $root = OrganizationalUnit::factory()->create([
            'name' => 'Subbagian Tata Usaha',
            'official_name' => 'Nama Pejabat Uji',
            'sort_order' => 10,
        ]);
        $child = OrganizationalUnit::factory()->create([
            'name' => 'Subdirektorat Lingkungan Hidup',
            'parent_id' => $root->id,
            'sort_order' => 20,
        ]);
        $inactive = OrganizationalUnit::factory()->inactive()->create([
            'name' => 'Unit Tidak Aktif',
            'parent_id' => $root->id,
        ]);
        $unpublished = OrganizationalUnit::factory()->unpublished()->create([
            'name' => 'Unit Tidak Terbit',
            'parent_id' => $root->id,
        ]);
        $orphan = OrganizationalUnit::factory()->create([
            'name' => 'Anak Dari Unit Tidak Terbit',
            'parent_id' => $unpublished->id,
        ]);

        $this->get(route('structure.index'))
            ->assertOk()
            ->assertSeeInOrder(['Subbagian Tata Usaha', 'Subdirektorat Lingkungan Hidup'])
            ->assertSee('Nama Pejabat Uji')
            ->assertSee('Nama pejabat belum tersedia.')
            ->assertDontSee($inactive->name)
            ->assertDontSee($unpublished->name)
            ->assertDontSee($orphan->name);
    }

    public function test_public_structure_page_renders_photo_and_responsive_markup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure/pejabat.jpg', 'foto');

        $root = OrganizationalUnit::factory()->create([
            'name' => 'Subdirektorat Kehutanan',
            'photo' => 'structure/pejabat.jpg',
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Substansi Energi Sumber Daya dan Mineral',
            'parent_id' => $root->id,
        ]);

        $response = $this->get(route('structure.index'))
            ->assertOk()
            ->assertSee('storage/structure/pejabat.jpg', false)
            ->assertSee('Unit bawahan');

        $this->assertStringContainsString('sm:flex-row', $response->getContent());
        $this->assertStringContainsString('border-l-2', $response->getContent());
    }

    public function test_administrator_can_create_an_organizational_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = OrganizationalUnit::factory()->create(['name' => 'Unit Induk Uji']);

        Livewire::actingAs($admin)
            ->test(CreateOrganizationalUnit::class)
            ->fillForm([
                'name' => 'Subdirektorat Pertanian Pangan',
                'official_name' => 'Nama Pejabat Uji',
                'parent_id' => $parent->id,
                'sort_order' => 30,
                'description' => 'Deskripsi tugas unit uji.',
                'is_active' => true,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizational_units', [
            'name' => 'Subdirektorat Pertanian Pangan',
            'official_name' => 'Nama Pejabat Uji',
            'parent_id' => $parent->id,
            'sort_order' => 30,
            'is_active' => true,
            'is_published' => true,
        ]);
    }

    public function test_administrator_can_update_an_organizational_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = OrganizationalUnit::factory()->create(['name' => 'Unit Induk Baru']);
        $unit = OrganizationalUnit::factory()->create(['name' => 'Nama Awal']);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm([
                'name' => 'Nama Setelah Diedit',
                'official_name' => 'Pejabat Setelah Diedit',
                'parent_id' => $parent->id,
                'sort_order' => 99,
                'description' => 'Deskripsi baru.',
                'is_active' => false,
                'is_published' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $unit->refresh();

        $this->assertSame('Nama Setelah Diedit', $unit->name);
        $this->assertSame('Pejabat Setelah Diedit', $unit->official_name);
        $this->assertSame($parent->id, $unit->parent_id);
        $this->assertSame(99, $unit->sort_order);
        $this->assertFalse($unit->is_active);
        $this->assertFalse($unit->is_published);
    }

    public function test_administrator_can_delete_a_leaf_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->create(['name' => 'Unit Akan Dihapus']);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->assertActionEnabled('delete')
            ->callAction('delete')
            ->callMountedAction();

        $this->assertDatabaseMissing('organizational_units', ['id' => $unit->id]);
    }

    public function test_unit_with_children_cannot_be_deleted_from_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = OrganizationalUnit::factory()->create(['name' => 'Unit Induk']);
        OrganizationalUnit::factory()->create([
            'name' => 'Unit Bawahan',
            'parent_id' => $parent->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $parent->getRouteKey()])
            ->assertActionDisabled('delete');

        $this->assertDatabaseHas('organizational_units', ['id' => $parent->id]);
    }

    public function test_hierarchy_relations_and_public_tree_are_built_from_parent_id(): void
    {
        $root = OrganizationalUnit::factory()->create(['name' => 'Unit Puncak', 'sort_order' => 10]);
        $child = OrganizationalUnit::factory()->create([
            'name' => 'Unit Anak',
            'parent_id' => $root->id,
            'sort_order' => 20,
        ]);
        $grandchild = OrganizationalUnit::factory()->create([
            'name' => 'Unit Cucu',
            'parent_id' => $child->id,
            'sort_order' => 30,
        ]);

        $this->assertTrue($child->parent->is($root));
        $this->assertTrue($grandchild->parent->is($child));
        $this->assertTrue($root->children->contains($child));
        $this->assertTrue($child->children->contains($grandchild));

        $this->assertSame(
            [$child->id, $grandchild->id],
            $root->descendantIds(),
        );

        $tree = OrganizationalUnit::publicTree();

        $this->assertCount(1, $tree);
        $this->assertTrue($tree->first()->is($root));
        $this->assertTrue($tree->first()->visibleChildren->first()->is($child));
        $this->assertTrue($tree->first()->visibleChildren->first()->visibleChildren->first()->is($grandchild));
    }

    public function test_parent_cannot_be_the_unit_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->create(['name' => 'Unit Mandiri']);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['parent_id' => $unit->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);

        $this->assertNull($unit->fresh()->parent_id);
    }

    public function test_circular_hierarchy_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $root = OrganizationalUnit::factory()->create(['name' => 'Unit Puncak']);
        $child = OrganizationalUnit::factory()->create([
            'name' => 'Unit Anak',
            'parent_id' => $root->id,
        ]);
        $grandchild = OrganizationalUnit::factory()->create([
            'name' => 'Unit Cucu',
            'parent_id' => $child->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $root->getRouteKey()])
            ->fillForm(['parent_id' => $grandchild->id])
            ->call('save')
            ->assertHasFormErrors(['parent_id']);

        $this->assertNull($root->fresh()->parent_id);
        $this->assertSame($root->id, $child->fresh()->parent_id);
        $this->assertSame($child->id, $grandchild->fresh()->parent_id);
    }

    public function test_publication_and_active_toggles_control_public_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->unpublished()->create(['name' => 'Unit Untuk Diterbitkan']);

        $this->get(route('structure.index'))->assertDontSee('Unit Untuk Diterbitkan');

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm([
                'is_active' => true,
                'is_published' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('structure.index'))->assertSee('Unit Untuk Diterbitkan');

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('structure.index'))->assertDontSee('Unit Untuk Diterbitkan');
    }

    public function test_administrator_can_upload_a_photo_when_creating_a_unit(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateOrganizationalUnit::class)
            ->fillForm([
                'name' => 'Unit Dengan Foto',
                'photo' => [UploadedFile::fake()->image('pejabat.jpg', 600, 600)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $unit = OrganizationalUnit::query()->where('name', 'Unit Dengan Foto')->firstOrFail();

        $this->assertIsString($unit->photo);
        Storage::disk('public')->assertExists($unit->photo);
    }

    public function test_photo_replacement_removes_the_old_file_after_save(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('structure/lama.jpg', 'foto lama');
        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Unit Dengan Foto Lama',
            'photo' => 'structure/lama.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm([
                'photo' => [UploadedFile::fake()->image('baru.jpg', 600, 600)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $newPath = $unit->fresh()->photo;

        $this->assertIsString($newPath);
        $this->assertNotSame('structure/lama.jpg', $newPath);
        Storage::disk('public')->assertMissing('structure/lama.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_photo_loss_without_explicit_removal_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('structure/lama.jpg', 'foto lama');
        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Unit Dengan Foto Lama',
            'photo' => 'structure/lama.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm(['photo' => null])
            ->call('save')
            ->assertHasFormErrors(['photo']);

        $this->assertSame('structure/lama.jpg', $unit->fresh()->photo);
        Storage::disk('public')->assertExists('structure/lama.jpg');
    }

    public function test_photo_can_be_removed_through_the_explicit_option(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('structure/lama.jpg', 'foto lama');
        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Unit Dengan Foto Lama',
            'photo' => 'structure/lama.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm([MediaIntegrityGuard::removalFlag('photo') => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($unit->fresh()->photo);
        Storage::disk('public')->assertMissing('structure/lama.jpg');
    }

    public function test_photo_survives_validation_failure(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('structure/lama.jpg', 'foto lama');
        $unit = OrganizationalUnit::factory()->create([
            'name' => 'Unit Dengan Foto Lama',
            'photo' => 'structure/lama.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(EditOrganizationalUnit::class, ['record' => $unit->getRouteKey()])
            ->fillForm([
                'photo' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
            ])
            ->call('save')
            ->assertHasFormErrors(['photo']);

        $this->assertSame('structure/lama.jpg', $unit->fresh()->photo);
        Storage::disk('public')->assertExists('structure/lama.jpg');
    }

    public function test_guest_is_redirected_and_administrator_and_editor_can_manage_the_structure(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->get('/admin/organizational-units')->assertRedirect('/admin/login');

        $this->actingAs($admin)->get('/admin/organizational-units')->assertOk();
        $this->actingAs($admin)->get('/admin/organizational-units/create')->assertOk();

        $this->actingAs($editor)->get('/admin/organizational-units')->assertOk();
        $this->actingAs($editor)->get('/admin/organizational-units/create')->assertOk();

        $unit = OrganizationalUnit::factory()->create();

        $this->actingAs($editor)->get("/admin/organizational-units/{$unit->id}/edit")->assertOk();
    }

    public function test_editor_can_create_a_unit_through_the_admin_form(): void
    {
        $editor = User::factory()->editor()->create();

        Livewire::actingAs($editor)
            ->test(CreateOrganizationalUnit::class)
            ->fillForm(['name' => 'Unit Dibuat Editor'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizational_units', ['name' => 'Unit Dibuat Editor']);
    }

    public function test_seeder_stores_the_reference_units_without_assuming_a_hierarchy(): void
    {
        $this->seed(OrganizationalUnitSeeder::class);

        $units = OrganizationalUnit::query()->orderBy('sort_order')->get();

        $this->assertCount(8, $units);
        $this->assertSame([
            'Subbagian Tata Usaha',
            'Subdirektorat Pertanian Pangan',
            'Subdirektorat Lingkungan Hidup',
            'Subdirektorat Kehutanan',
            'Subdirektorat Pertanahan dan Penataan Ruang',
            'Subdirektorat Pertanian dan Pangan',
            'Substansi Energi Sumber Daya dan Mineral',
            'Substansi Koordinasi Kewilayahan dan Perubahan Iklim',
        ], $units->pluck('name')->all());

        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->parent_id === null));
        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->official_name === null));
        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->photo === null));
        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->is_published === false));
    }
}
