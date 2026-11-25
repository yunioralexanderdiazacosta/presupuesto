<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Conexión con asistentes de IA (MCP)
        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(config('mcp.rate_limit_per_minute'))
            ->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('mcp-register', fn (Request $request) => Limit::perHour(30)->by($request->ip()));
        RateLimiter::for('mcp-token', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            if (config('mcp.enabled')) {
                require base_path('routes/mcp.php');
            }

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
