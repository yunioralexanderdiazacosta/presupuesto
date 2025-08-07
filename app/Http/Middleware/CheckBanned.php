<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->check() && (auth()->user()->status == 0)){
            auth()->guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Su cuenta está suspendida, comuníquese con el administrador'); 
        }

        if(auth()->check() && !$request->session()->has('price')){
            $prices = Http::withOptions(["verify"=>false])->get("https://mindicador.cl/api") ?? '';
            $price  = $prices ? $prices['dolar']['valor'] : '';
            session(['price' => $price]);
        }

        return $next($request);
    }
}
