<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user instanceof User
            || ! $user->isPasswordChangeRequired()
            || $request->routeIs([
                'filament.*.auth.profile',
                'filament.*.auth.logout',
            ])
        ) {
            return $next($request);
        }

        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();

        return redirect()
            ->to($panel->getProfileUrl() ?? '/');
    }
}
