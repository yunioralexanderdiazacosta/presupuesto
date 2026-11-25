<?php

namespace App\Http\Middleware;

use App\Support\Mcp\McpUrls;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica las peticiones al servidor MCP con el token OAuth emitido a un asistente de IA.
 * Responde con los encabezados WWW-Authenticate que los asistentes usan para iniciar el flujo de autorización.
 */
class AuthenticateMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->challenge($request, 401, $request->bearerToken() ? 'invalid_token' : null);
        }

        // Cuenta suspendida o sin empresa: el token deja de ser útil aunque siga vigente.
        if ((int) $user->status === 0 || ! $user->team_id) {
            // Revocar es un refuerzo: aunque falle, la petición igual se rechaza.
            rescue(fn () => $user->token()?->revoke(), report: true);

            return $this->challenge($request, 401, 'invalid_token');
        }

        if (! $user->token()?->can(config('mcp.scope'))) {
            return $this->challenge($request, 403, 'insufficient_scope');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function challenge(Request $request, int $status, ?string $error): Response
    {
        $parts = ['resource_metadata="' . McpUrls::resourceMetadata() . '"', 'scope="' . config('mcp.scope') . '"'];

        if ($error) {
            array_unshift($parts, 'error="' . $error . '"');
        }

        return response()->json(
            ['error' => $error ?? 'unauthorized', 'error_description' => 'Se requiere autorización.'],
            $status,
            ['WWW-Authenticate' => 'Bearer ' . implode(', ', $parts)]
        );
    }
}
