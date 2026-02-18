<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Redirect;

class checkSelectedBudget
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Excluir las rutas de selección de temporada para evitar loops
        if ($request->routeIs('select.budget') || $request->routeIs('select.seasons.save')) {
            return $next($request);
        }

        if(auth()->check() && auth()->user()->status == 1 && auth()->user()->hasRole('Admin')){
            $user = auth()->user();

            // BUG FIX: has() devuelve boolean, no debería compararse con == null
            if ($user && $user->team && $user->team->seasons()->count() > 0 && !$request->session()->has('season_id')) {
                return Redirect::route('select.budget');
            }
        }
        
        return $next($request);
    }
}
