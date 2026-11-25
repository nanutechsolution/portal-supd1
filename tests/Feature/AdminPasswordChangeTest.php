<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_change_requirement_may_open_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_user_with_change_requirement_is_redirected_to_the_change_password_page(): void
    {
        $user = User::factory()->admin()->mustChangePassword()->create();

        $this->actingAs($user)
            ->get('/admin/posts')
            ->assertRedirect('/admin/ubah-kata-sandi');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/admin/ubah-kata-sandi');
    }

    public function test_flagged_user_can_open_and_use_the_change_password_page(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->get('/admin/ubah-kata-sandi')
            ->assertOk()
            ->assertSee('Ubah Kata Sandi');
    }

    public function test_flagged_user_is_sent_to_the_change_password_page_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'wajib@example.com',
            'must_change_password' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'wajib@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);

        $this->get('/admin')->assertRedirect('/admin/ubah-kata-sandi');
    }

    public function test_user_with_change_requirement_may_still_log_out(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_flag_is_cleared_after_password_is_changed(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'password',
                'password' => 'NewPassword123',
                'passwordConfirmation' => 'NewPassword123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user = $user->fresh();

        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
    }

    public function test_wrong_current_password_is_rejected_and_password_is_kept(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'wrong-password',
                'password' => 'NewPassword123',
                'passwordConfirmation' => 'NewPassword123',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $user = $user->fresh();

        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_new_password_must_be_different_from_the_current_one(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'password',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }

    public function test_new_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'password',
                'password' => 'NewPassword123',
                'passwordConfirmation' => 'OtherPassword123',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }

    public function test_new_password_requires_stronger_strength(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'password',
                'password' => 'weak',
                'passwordConfirmation' => 'weak',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }

    public function test_users_created_by_admin_are_flagged_to_change_password(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'User Uji',
                'email' => 'user-uji@example.com',
                'password' => 'PasswordUji123',
                'role' => 'editor',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'user-uji@example.com',
            'must_change_password' => true,
        ]);
    }

    public function test_admin_setting_a_password_flags_the_user_to_change_it(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['must_change_password' => false]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'password' => 'PasswordBaru123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user = $user->fresh();

        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaru123', $user->password));
    }

    public function test_user_list_shows_who_still_has_to_change_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'Pengguna Wajib Ganti',
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Pengguna Wajib Ganti')
            ->assertSee('Perlu diganti');
    }
}
