<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            try {
                // Timeout más agresivo y mejor manejo de SSL en producción
                $prices = Http::withOptions([
                        "verify" => false,
                        "connect_timeout" => 2, // Timeout de conexión
                        "timeout" => 3, // Timeout total
                        "http_errors" => false // No lanzar excepciones en errores HTTP
                    ])
                    ->get("https://mindicador.cl/api");
                
                $price = $prices->successful() && isset($prices['dolar']['valor']) 
                    ? $prices['dolar']['valor'] 
                    : 900; // Valor por defecto si la API falla
                    
            } catch (\Throwable $e) {
                // Capturar CUALQUIER error (incluyendo errores de conexión SSL/TLS)
                Log::warning('Mindicador API no disponible: ' . $e->getMessage());
                $price = 900;
            }
            
            session(['price' => $price]);
        }

        return $next($request);
    }
}
