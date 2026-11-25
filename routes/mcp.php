<?php

use App\Http\Controllers\Mcp\AuthorizationServerMetadataController;
use App\Http\Controllers\Mcp\McpServerController;
use App\Http\Controllers\Mcp\ProtectedResourceMetadataController;
use App\Http\Controllers\Mcp\RegisterOAuthClientController;
use App\Http\Middleware\AuthenticateMcp;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;

/*
|--------------------------------------------------------------------------
| Conexión con asistentes de IA (servidor MCP de solo lectura + OAuth 2.1)
|--------------------------------------------------------------------------
|
| Se cargan desde RouteServiceProvider sin prefijo. Solo se exponen los endpoints OAuth necesarios
| (las rutas por defecto de Passport están desactivadas en AuthServiceProvider).
|
*/

// Sin sesión ni CSRF: los consumen los servidores de los asistentes de IA.
Route::middleware('api')->group(function () {
    Route::get('/.well-known/oauth-protected-resource', ProtectedResourceMetadataController::class);
    Route::get('/.well-known/oauth-protected-resource/mcp', ProtectedResourceMetadataController::class)->name('mcp.metadata.resource');
    Route::get('/.well-known/oauth-authorization-server', AuthorizationServerMetadataController::class)->name('mcp.metadata.server');

    Route::post('/oauth/register', RegisterOAuthClientController::class)
        ->middleware('throttle:mcp-register')
        ->name('oauth.register');

    Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])
        ->middleware('throttle:mcp-token')
        ->name('passport.token');

    Route::post('/mcp', McpServerController::class)
        ->middleware([AuthenticateMcp::class, 'throttle:mcp'])
        ->name('mcp.server');

    Route::match(['GET', 'DELETE'], '/mcp', [McpServerController::class, 'methodNotAllowed']);
});

// Pantalla de consentimiento: el usuario inicia sesión en el portal y autoriza (o rechaza) al asistente.
Route::middleware('web')->group(function () {
    Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])
        ->name('passport.authorizations.authorize');

    Route::middleware('auth')->group(function () {
        Route::post('/oauth/authorize', [ApproveAuthorizationController::class, 'approve'])
            ->name('passport.authorizations.approve');
        Route::delete('/oauth/authorize', [DenyAuthorizationController::class, 'deny'])
            ->name('passport.authorizations.deny');
    });
});
