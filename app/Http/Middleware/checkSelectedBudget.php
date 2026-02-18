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
            \Log::info('checkSelectedBudget: Route excluded (select.budget or save)', [
                'route' => $request->route()?->getName(),
            ]);
            return $next($request);
        }

        if(auth()->check() && auth()->user()->status == 1 && auth()->user()->hasRole('Admin')){
            $user = auth()->user();

            \Log::info('checkSelectedBudget: Checking session', [
                'route' => $request->route()?->getName(),
                'user_id' => $user->id,
                'has_season_id' => $request->session()->has('season_id'),
                'session_season_id' => session('season_id'),
                'seasons_count' => $user->team?->seasons()->count() ?? 0,
            ]);

            // BUG FIX: has() devuelve boolean, no debería compararse con == null
            if ($user && $user->team && $user->team->seasons()->count() > 0 && !$request->session()->has('season_id')) {
                \Log::warning('checkSelectedBudget: REDIRECTING to select.budget', [
                    'from_route' => $request->route()?->getName(),
                    'user_id' => $user->id,
                ]);
                return Redirect::route('select.budget');
            }
        }
        
        \Log::info('checkSelectedBudget: Passed, continuing', [
            'route' => $request->route()?->getName(),
        ]);
        
        return $next($request);
    }
}
