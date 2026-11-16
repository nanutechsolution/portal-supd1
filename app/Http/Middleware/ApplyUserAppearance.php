<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Appearance;
use Closure;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the signed-in user's accent palette to the current Filament panel.
 *
 * The palette is registered before Filament renders its styles, so the primary
 * colour variables are part of the first paint and never flash the default
 * accent. Registration is a no-op for guests, whose panel keeps the default
 * palette configured in AdminPanelProvider.
 */
final class ApplyUserAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $appearance = Appearance::from($user->appearance);

            FilamentColor::register([
                'primary' => Appearance::palette($appearance->accent),
            ]);
        }

        return $next($request);
    }
}
