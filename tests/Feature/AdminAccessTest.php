<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_administrator_can_open_dashboard_and_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/manage-settings')->assertOk();
    }

    public function test_editor_can_manage_posts_but_not_users(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/posts')->assertOk();
        $this->actingAs($editor)->get('/admin/posts/create')->assertOk();
        $this->actingAs($editor)->get('/admin/users')->assertForbidden();
        $this->actingAs($editor)->get('/admin/users/create')->assertForbidden();
    }

    public function test_editor_cannot_open_website_settings(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/manage-settings')->assertForbidden();
    }

    public function test_administrator_can_update_website_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageSettings::class)
            ->fillForm([
                'site_name' => 'Portal Uji SUPD I',
                'site_short_name' => 'SUPD I',
                'site_tagline' => 'Tagline pengujian.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Portal Uji SUPD I',
        ]);

        Setting::flushCache();

        $this->assertSame('Portal Uji SUPD I', Setting::get('site_name'));
    }

    public function test_user_model_reports_role_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($editor->isAdmin());
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }
}
