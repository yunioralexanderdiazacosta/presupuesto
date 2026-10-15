<?php

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bloquea el acceso a rutas de módulos que no estén disponibles para el usuario autenticado:
 * deshabilitados para su empresa (team) o fuera de su lista de módulos si está restringido.
 * Super Admin nunca se ve afectado.
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && ModuleAccess::isBlockedFor($user, $request->route()?->getName())) {
            abort(403, 'No tienes acceso a este módulo. Contacta al administrador del sistema para más información.');
        }

        return $next($request);
    }
}
