<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $lastActivity = session('last_activity');
            $timeout = config('session.lifetime') * 60; // segundos
            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                auth()->guard('web')->logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect('/login')->with('message', 'Tu sesión ha caducado por inactividad.');
            }
        }
        return $next($request);
    }
}
