<?php

namespace Tests\Feature;

use App\Enums\OrganizationalLevel;
use App\Filament\Resources\OrganizationalUnits\Pages\CreateOrganizationalUnit;
use App\Filament\Resources\OrganizationalUnits\Pages\EditOrganizationalUnit;
use App\Models\OrganizationalUnit;
use App\Models\Setting;
use App\Models\User;
use App\Support\MediaIntegrityGuard;
use Database\Seeders\OrganizationalStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationalStructureTest extends TestCase
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

        $response = $this->get(route('structure.index'))
            ->assertOk()
            ->assertSeeInOrder(['Subbagian Tata Usaha', 'Subdirektorat Lingkungan Hidup'])
            ->assertSee('Nama Pejabat Uji')
            ->assertSee('org-card__position', false)
            ->assertSee('org-card__fallback', false)
            ->assertDontSee('Nama pejabat belum tersedia.')
            ->assertDontSee($inactive->name)
            ->assertDontSee($unpublished->name)
            ->assertDontSee($orphan->name);

        $this->assertSame(2, substr_count($response->getContent(), 'class="org-card '));
    }

    public function test_public_structure_page_renders_photo_and_scrollable_chart_markup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure/pejabat.jpg', 'foto');

        $root = OrganizationalUnit::factory()->create([
            'name' => 'Subdirektorat Kehutanan',
            'official_name' => 'Pejabat Berfoto',
            'photo' => 'structure/pejabat.jpg',
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Substansi Energi Sumber Daya dan Mineral',
            'parent_id' => $root->id,
        ]);

        $this->get(route('structure.index'))
            ->assertOk()
            ->assertSee('storage/structure/pejabat.jpg', false)
            ->assertSee('alt="Foto Pejabat Berfoto"', false)
            ->assertSee('org-chart__scroll', false)
            ->assertSee('org-chart__canvas', false)
            ->assertSee('aria-label="Bagan struktur organisasi', false)
            ->assertSee('tabindex="0"', false)
            ->assertDontSee('Unit bawahan');
    }

    public function test_public_chart_uses_a_card_variant_and_photo_rule_for_each_level(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure/fungsional.jpg', 'foto');

        $root = OrganizationalUnit::factory()->create([
            'name' => 'Direktur Uji',
            'official_name' => 'Rahmat Uji',
            'level' => OrganizationalLevel::Pimpinan,
            'sort_order' => 1,
        ]);
        $kabag = OrganizationalUnit::factory()->create([
            'name' => 'Kepala Bagian Uji',
            'official_name' => 'Sinta Uji',
            'level' => OrganizationalLevel::KepalaBagian,
            'parent_id' => $root->id,
            'sort_order' => 10,
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Kepala Subbagian Uji',
            'level' => OrganizationalLevel::Kasubbag,
            'parent_id' => $kabag->id,
            'sort_order' => 10,
        ]);
        $kapokja = OrganizationalUnit::factory()->create([
            'name' => 'Kepala Kelompok Kerja Uji',
            'official_name' => 'Dewi Uji',
            'level' => OrganizationalLevel::Kapokja,
            'parent_id' => $root->id,
            'sort_order' => 20,
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Ketua Tim Uji',
            'official_name' => 'Andi Uji',
            'level' => OrganizationalLevel::Katim,
            'parent_id' => $kapokja->id,
            'sort_order' => 10,
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Jabatan Fungsional Uji',
            'official_name' => 'Bagus Uji',
            'level' => OrganizationalLevel::Fungsional,
            'photo' => 'structure/fungsional.jpg',
            'parent_id' => $root->id,
            'sort_order' => 30,
        ]);

        $response = $this->get(route('structure.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'org-card--leader',
                'org-card--section',
                'org-card--subsection',
                'org-card--work-group',
                'org-card--team',
                'org-card--functional',
            ], false)
            ->assertSee('Rahmat Uji')
            ->assertSee('Sinta Uji')
            ->assertSee('Dewi Uji')
            ->assertSee('Andi Uji')
            ->assertSee('Bagus Uji')
            ->assertSee('>RU</span>', false)
            ->assertSee('>KS</span>', false)
            ->assertDontSee('storage/structure/fungsional.jpg', false);

        $content = (string) $response->getContent();

        foreach (['leader', 'section', 'subsection', 'work-group', 'team', 'functional'] as $variant) {
            $this->assertSame(1, substr_count($content, 'org-card--'.$variant), "Variant {$variant} tidak muncul tepat satu kali.");
        }

        $this->assertSame(5, substr_count($content, 'org-card__media'));
        $this->assertSame(5, substr_count($content, 'org-card__fallback'));
    }

    public function test_units_without_a_position_are_skipped_and_their_children_are_promoted(): void
    {
        $placeholder = OrganizationalUnit::factory()->create([
            'name' => '',
            'level' => OrganizationalLevel::Pimpinan,
        ]);
        OrganizationalUnit::factory()->create([
            'name' => 'Kepala Bagian Diangkat',
            'official_name' => 'Pejabat Diangkat',
            'level' => OrganizationalLevel::KepalaBagian,
            'parent_id' => $placeholder->id,
        ]);

        $response = $this->get(route('structure.index'))
            ->assertOk()
            ->assertSee('Kepala Bagian Diangkat')
            ->assertSee('Pejabat Diangkat');

        $this->assertSame(1, substr_count($response->getContent(), 'class="org-card '));
        $this->assertStringNotContainsString('org-card--leader', $response->getContent());
    }

    public function test_chart_heading_and_watermark_follow_site_identity_settings(): void
    {
        Setting::putMany([
            'site_ministry_line2' => 'Kementerian Uji',
            'site_directorate' => 'Direktorat Uji Coba',
            'site_ministry_line1' => 'Direktorat Jenderal Uji',
            'site_short_name' => 'DUJI',
        ]);

        OrganizationalUnit::factory()->create(['name' => 'Pimpinan Uji']);

        $html = (string) $this->get(route('structure.index'))->assertOk()->getContent();
        $chart = Str::between($html, '<section class="org-chart"', '</section>');

        $this->assertStringContainsString('org-chart__institution">Kementerian Uji', $chart);
        $this->assertStringContainsString('org-chart__title">Direktorat Uji Coba', $chart);
        $this->assertStringContainsString('org-chart__subtitle">Direktorat Jenderal Uji', $chart);
        $this->assertStringContainsString('org-chart__watermark" aria-hidden="true">DUJI', $chart);

        Setting::putMany(['site_ministry_line1' => '']);

        $html = (string) $this->get(route('structure.index'))->assertOk()->getContent();
        $chart = Str::between($html, '<section class="org-chart"', '</section>');

        $this->assertStringContainsString('org-chart__institution">Kementerian Uji', $chart);
        $this->assertStringNotContainsString('org-chart__subtitle', $chart);
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
                'level' => OrganizationalLevel::KepalaBagian->value,
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
            'level' => OrganizationalLevel::KepalaBagian->value,
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
                'level' => OrganizationalLevel::Kapokja->value,
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
        $this->assertSame(OrganizationalLevel::Kapokja, $unit->level);
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
                'level' => OrganizationalLevel::KepalaBagian->value,
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
            ->fillForm([
                'name' => 'Unit Dibuat Editor',
                'level' => OrganizationalLevel::Katim->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizational_units', ['name' => 'Unit Dibuat Editor']);
    }

    public function test_seeder_builds_one_idempotent_hierarchy_with_levels(): void
    {
        Storage::fake('public');

        $legacyRoot = OrganizationalUnit::factory()->create(['name' => 'Jokowi']);
        OrganizationalUnit::factory()->create([
            'name' => 'Subdirektorat Pertanian Pangan',
            'parent_id' => $legacyRoot->id,
        ]);

        $this->seed(OrganizationalStructureSeeder::class);
        $this->seed(OrganizationalStructureSeeder::class);

        $units = OrganizationalUnit::query()->get();

        $this->assertCount(13, $units);
        $this->assertSame(1, $units->whereNull('parent_id')->count());
        $this->assertSame(0, $units->whereNull('parent_id')->where('is_published', false)->count());
        $this->assertSame(0, $units->where('name', 'Jokowi')->count());
        $this->assertSame(0, $units->where('name', 'Subdirektorat Pertanian Pangan')->count());

        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->level instanceof OrganizationalLevel));
        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => filled($unit->official_name)));
        $this->assertTrue($units->every(fn (OrganizationalUnit $unit): bool => $unit->is_active && $unit->is_published));

        $pimpinan = $units->firstWhere('name', 'Direktur Sinkronisasi Urusan Pemerintahan Daerah I');
        $kabag = $units->firstWhere('name', 'Kepala Bagian Tata Usaha');
        $kasubbag = $units->firstWhere('name', 'Kepala Subbagian Tata Usaha');
        $fungsional = $units->firstWhere('level', OrganizationalLevel::Fungsional);

        $this->assertInstanceOf(OrganizationalUnit::class, $pimpinan);
        $this->assertInstanceOf(OrganizationalUnit::class, $kabag);
        $this->assertInstanceOf(OrganizationalUnit::class, $kasubbag);
        $this->assertInstanceOf(OrganizationalUnit::class, $fungsional);

        $this->assertSame(OrganizationalLevel::Pimpinan, $pimpinan->level);
        $this->assertNull($pimpinan->parent_id);
        $this->assertSame(OrganizationalLevel::KepalaBagian, $kabag->level);
        $this->assertSame($pimpinan->id, $kabag->parent_id);
        $this->assertSame(OrganizationalLevel::Kasubbag, $kasubbag->level);
        $this->assertSame($kabag->id, $kasubbag->parent_id);
        $this->assertNull($fungsional->photo);

        $kapokjas = $units->where('level', OrganizationalLevel::Kapokja);

        $this->assertCount(3, $kapokjas);

        foreach ($kapokjas as $kapokja) {
            $this->assertSame($pimpinan->id, $kapokja->parent_id);
            $this->assertCount(2, $kapokja->visibleChildren);
            $this->assertTrue($kapokja->visibleChildren->every(
                fn (OrganizationalUnit $unit): bool => $unit->level === OrganizationalLevel::Katim,
            ));
        }
    }

    public function test_level_enum_and_legacy_fallbacks_are_available_for_the_public_chart(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('structure/fungsional.jpg', 'foto');

        $fungsional = OrganizationalUnit::factory()->create([
            'name' => 'Jabatan Fungsional Perencana',
            'official_name' => 'Dr. Bagus Prakoso',
            'level' => null,
            'photo' => 'structure/fungsional.jpg',
        ]);
        $katim = OrganizationalUnit::factory()->create([
            'name' => 'Ketua Tim Anggaran',
            'level' => null,
        ]);
        $pimpinan = OrganizationalUnit::factory()->create([
            'name' => 'Unit Puncak',
            'level' => null,
        ]);

        $this->assertSame(OrganizationalLevel::Fungsional, $fungsional->resolvedLevel());
        $this->assertFalse($fungsional->resolvedLevel()->hasPhoto());
        $this->assertNull($fungsional->cardPhotoUrl());
        $this->assertSame('BP', $fungsional->initials());
        $this->assertSame(OrganizationalLevel::Katim, $katim->resolvedLevel());
        $this->assertSame(OrganizationalLevel::Pimpinan, $pimpinan->resolvedLevel());

        $this->assertSame([
            'pimpinan' => 'Pimpinan',
            'kepala_bagian' => 'Kepala Bagian',
            'fungsional' => 'Jabatan Fungsional',
            'kasubbag' => 'Kepala Subbagian',
            'kapokja' => 'Kepala Kelompok Kerja',
            'katim' => 'Ketua Tim',
        ], OrganizationalLevel::options());
    }
}
