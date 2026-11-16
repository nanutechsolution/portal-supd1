<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AppearanceSettings;
use App\Filament\Widgets\LatestPosts;
use App\Filament\Widgets\PortalStats;
use App\Http\Middleware\ApplyUserAppearance;
use App\Models\Setting;
use App\Models\User;
use App\Support\Appearance;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseTransactions()
            ->brandName(Setting::get('site_short_name') ?: 'Portal SUPD I')
            ->favicon(Setting::mediaUrl('site_favicon') ?: asset('favicon.svg'))
            ->colors([
                'primary' => Color::hex('#0b4f63'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->themeSwitcher(false)
            ->userMenuItems([
                Action::make('appearance')
                    ->label('Tampilan')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->url(fn (): string => AppearanceSettings::getUrl())
                    ->sort(0),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): View => view('filament.appearance.head', [
                    'appearance' => $this->appearanceForCurrentUser(),
                ]),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.appearance.styles', [
                    'appearance' => $this->appearanceForCurrentUser(),
                ]),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Publikasi'),
                NavigationGroup::make()
                    ->label('Situs'),
                NavigationGroup::make()
                    ->label('Sistem')
                    ->collapsed(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PortalStats::class,
                LatestPosts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                ApplyUserAppearance::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Resolve the palette preferences for the authenticated panel user.
     *
     * Guests (for example on the login screen) keep the default panel palette.
     */
    private function appearanceForCurrentUser(): ?Appearance
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? Appearance::from($user->appearance) : null;
    }
}
