<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bloquea el acceso a rutas cuyo módulo esté deshabilitado para la empresa (team)
 * del usuario autenticado. Super Admin nunca se ve afectado.
 */
class CheckTeamModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->hasRole('Super Admin')) {
            $routeName = $request->route()?->getName();

            if (ModuleAccess::isBlocked($user->team, $routeName)) {
                abort(403, 'Módulo no habilitado. Contacta al administrador del sistema para más información.');
            }
        }

        return $next($request);
    }
}
