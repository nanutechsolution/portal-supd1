<?php

namespace Tests\Feature;

use App\Filament\Pages\AppearanceSettings;
use App\Models\User;
use App\Support\Appearance;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/tampilan')->assertRedirect('/admin/login');
    }

    public function test_every_signed_in_user_can_open_the_appearance_page(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->editor()->create()] as $user) {
            $this->actingAs($user)->get('/admin/tampilan')->assertOk();
        }
    }

    public function test_defaults_are_used_when_no_preferences_are_stored(): void
    {
        $user = User::factory()->create(['appearance' => null]);

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee("const mode = 'system';", false)
            ->assertSee("const density = 'comfortable';", false)
            ->assertSee('const sidebarExpanded = true;', false)
            ->assertDontSee(Appearance::paletteForSidebar('slate')[100], false);
    }

    public function test_user_can_save_preferences_without_affecting_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AppearanceSettings::class)
            ->set('mode', 'dark')
            ->set('accent', 'green')
            ->set('density', 'compact')
            ->set('sidebar', 'collapsed')
            ->set('sidebarColor', 'teal')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(AppearanceSettings::getUrl());

        $this->assertSame([
            'mode' => 'dark',
            'accent' => 'green',
            'density' => 'compact',
            'sidebar' => 'collapsed',
            'sidebarColor' => 'teal',
        ], $user->fresh()->appearance);

        $this->assertNull($other->fresh()->appearance);
    }

    public function test_invalid_values_are_rejected_and_previous_preferences_are_kept(): void
    {
        $user = User::factory()->create([
            'appearance' => Appearance::defaults()->toArray(),
        ]);

        Livewire::actingAs($user)
            ->test(AppearanceSettings::class)
            ->set('mode', 'neon')
            ->set('accent', 'red')
            ->set('density', 'ultra')
            ->set('sidebar', 'hidden')
            ->set('sidebarColor', 'rainbow')
            ->call('save')
            ->assertHasErrors(['mode', 'accent', 'density', 'sidebar', 'sidebarColor']);

        $this->assertSame(Appearance::defaults()->toArray(), $user->fresh()->appearance);
    }

    public function test_saved_preferences_are_applied_to_the_panel_on_the_first_paint(): void
    {
        $user = User::factory()->create([
            'appearance' => [
                'mode' => 'dark',
                'accent' => 'green',
                'density' => 'compact',
                'sidebar' => 'collapsed',
                'sidebarColor' => 'teal',
            ],
        ]);

        $teal = Appearance::paletteForSidebar('teal');

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee("const mode = 'dark';", false)
            ->assertSee("const density = 'compact';", false)
            ->assertSee('const sidebarExpanded = false;', false)
            ->assertSee('fi-body-has-sidebar-collapsible-on-desktop', false)
            ->assertSee(Appearance::palette('green')[500], false)
            ->assertDontSee(Appearance::palette('orange')[500], false)
            ->assertSee($teal[100], false)
            ->assertSee($teal[900], false)
            ->assertDontSee($teal[500], false);
    }

    public function test_sidebar_color_is_independent_from_the_accent_color(): void
    {
        $user = User::factory()->create([
            'appearance' => [
                'mode' => 'light',
                'accent' => 'orange',
                'density' => 'comfortable',
                'sidebar' => 'expanded',
                'sidebarColor' => 'teal',
            ],
        ]);

        $teal = Appearance::paletteForSidebar('teal');
        $orange = Appearance::paletteForSidebar('orange');

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee(Appearance::palette('orange')[500], false)
            ->assertSee('background-color: '.$teal[100], false)
            ->assertSee('background-color: '.$teal[900], false)
            ->assertDontSee('background-color: '.$orange[100], false)
            ->assertDontSee($teal[500], false);

        Livewire::actingAs($user)
            ->test(AppearanceSettings::class)
            ->set('sidebarColor', 'purple')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('orange', $user->fresh()->appearance['accent']);
        $this->assertSame('purple', $user->fresh()->appearance['sidebarColor']);
    }

    public function test_corrupted_preferences_fall_back_to_safe_defaults(): void
    {
        $user = User::factory()->create([
            'appearance' => [
                'mode' => '<script>alert(1)</script>',
                'accent' => 'red; background:url(javascript:alert(1))',
                'density' => 'ultra',
                'sidebar' => 'hidden',
                'sidebarColor' => '<script>alert(2)</script>',
            ],
        ]);

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('javascript:alert(1)', false)
            ->assertSee("const mode = 'system';", false)
            ->assertSee("const density = 'comfortable';", false)
            ->assertSee('const sidebarExpanded = true;', false)
            ->assertDontSee(Appearance::paletteForSidebar('orange')[100], false);
    }

    public function test_preferences_survive_a_real_logout_and_login_cycle(): void
    {
        $user = User::factory()->create([
            'appearance' => [
                'mode' => 'dark',
                'accent' => 'orange',
                'density' => 'compact',
                'sidebar' => 'collapsed',
                'sidebarColor' => 'purple',
            ],
        ]);

        $this->signIn($user);

        // Simulate a new HTTP request lifecycle (as PHP-FPM / Octane does),
        // so request-scoped services such as Filament's ColorManager do not
        // reuse state cached while rendering the Livewire login component.
        $this->simulateNewRequest();

        $purple = Appearance::paletteForSidebar('purple');

        $this->get('/admin')
            ->assertOk()
            ->assertSee("const mode = 'dark';", false)
            ->assertSee(Appearance::palette('orange')[500], false)
            ->assertSee($purple[100], false)
            ->assertSee($purple[900], false);

        Auth::logout();
        $this->flushSession();
        $this->assertGuest();

        $this->signIn($user->fresh());

        $this->simulateNewRequest();

        $this->get('/admin')
            ->assertOk()
            ->assertSee("const mode = 'dark';", false)
            ->assertSee('const sidebarExpanded = false;', false)
            ->assertSee("const density = 'compact';", false)
            ->assertSee(Appearance::palette('orange')[500], false)
            ->assertSee($purple[100], false)
            ->assertSee($purple[900], false);
    }

    private function signIn(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Forget container scoped instances and resolved facades between simulated
     * requests, mirroring what PHP-FPM and Laravel Octane do per request.
     */
    private function simulateNewRequest(): void
    {
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();
    }
}
