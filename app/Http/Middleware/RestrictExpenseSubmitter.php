<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Limita a los usuarios cuyo único rol funcional es "Rendidor"
 * a las rutas del módulo de Rendiciones de Gastos y a un puñado de rutas
 * básicas de la aplicación (sesión, perfil, selección de temporada, etc).
 */
class RestrictExpenseSubmitter
{
    protected array $allowedNames = [
        'home', 'home.index', 'logout',
        'system-guide', 'faq',
        'select.budget', 'select.seasons.save',
        'weather',
    ];

    protected array $allowedPrefixes = [
        'expense-reports.',
        'api.suppliers.',
        'api.pending-expense-items',
        'sidebar.',
        'profile', 'user-profile-information', 'user-password',
        'two-factor', 'other-browser-sessions', 'current-user-photo',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->hasRole('Rendidor') && !$user->hasAnyRole(['Admin', 'Super Admin'])) {
            $routeName = $request->route()?->getName();

            $allowed = $routeName && (
                in_array($routeName, $this->allowedNames, true)
                || collect($this->allowedPrefixes)->contains(fn ($prefix) => Str::startsWith($routeName, $prefix))
            );

            if (!$allowed) {
                abort(403, 'Tu usuario solo tiene acceso al módulo de Rendiciones de Gastos.');
            }
        }

        return $next($request);
    }
}
