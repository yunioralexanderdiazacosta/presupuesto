<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshTokenRepository;

/** Revoca el acceso de un asistente de IA: sus tokens (y la posibilidad de renovarlos) dejan de funcionar al instante. */
class RevokeMcpConnectionController extends Controller
{
    public function __invoke(Request $request, string $client, RefreshTokenRepository $refreshTokens): JsonResponse
    {
        $tokens = Passport::token()->newQuery()
            ->where('user_id', $request->user()->id)
            ->where('client_id', $client)
            ->where('revoked', false)
            ->get();

        foreach ($tokens as $token) {
            $token->revoke();
            $refreshTokens->revokeRefreshTokensByAccessTokenId($token->id);
        }

        Log::channel('mcp')->info('mcp.connection_revoked', [
            'user_id' => $request->user()->id,
            'client_id' => $client,
            'tokens' => $tokens->count(),
        ]);

        return response()->json(['revoked' => $tokens->count()]);
    }
}
