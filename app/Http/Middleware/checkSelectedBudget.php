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
        if(auth()->check() && auth()->user()->status == 1 && auth()->user()->hasRole('Admin')){
            $user = auth()->user();

            if ($user && $user->team && $user->team->budgets()->count() > 0 && $request->session()->has('budget_id') == null) {
                return Redirect::route('select.budget');
            }
        }
        return $next($request);
    }
}
