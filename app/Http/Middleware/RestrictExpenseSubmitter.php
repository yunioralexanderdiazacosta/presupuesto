<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Limita a los usuarios cuyo ÚNICO rol asignado es "Rendidor" (nada más marcado)
 * a las rutas del módulo de Rendiciones de Gastos y a un puñado de rutas
 * básicas de la aplicación (sesión, perfil, selección de temporada, etc).
 * Si el usuario tiene cualquier otro rol además de Rendidor, no se restringe.
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

        if ($user && $user->hasRole('Rendidor') && $user->getRoleNames()->count() === 1) {
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
