<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;

/** Conexiones con asistentes de IA que el usuario autenticado tiene activas. */
class ListMcpConnectionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tokens = Passport::token()->newQuery()
            ->where('user_id', $request->user()->id)
            ->where('revoked', false)
            ->with('client:id,name')
            ->get();

        // El token de acceso dura poco; la conexión sigue activa mientras pueda renovarse.
        $renewable = Passport::refreshToken()->newQuery()
            ->whereIn('access_token_id', $tokens->pluck('id'))
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->pluck('access_token_id')
            ->all();

        $connections = $tokens
            ->filter(fn ($t) => $t->client && ($t->expires_at?->isFuture() || in_array($t->id, $renewable, true)))
            ->groupBy('client_id')
            ->map(fn ($group) => [
                'id' => (string) $group->first()->client_id,
                'name' => $group->first()->client->name,
                'authorized_at' => $group->min('created_at')?->toIso8601String(),
                'last_renewed_at' => $group->max('created_at')?->toIso8601String(),
            ])
            ->sortByDesc('last_renewed_at')
            ->values();

        return response()->json($connections);
    }
}
