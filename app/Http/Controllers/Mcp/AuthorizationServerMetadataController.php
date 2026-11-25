<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Support\Mcp\McpUrls;
use Illuminate\Http\JsonResponse;

/** RFC 8414: descubrimiento de los endpoints OAuth (autorizar, token y registro dinámico). */
class AuthorizationServerMetadataController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'issuer' => McpUrls::base(),
            'authorization_endpoint' => McpUrls::authorizationEndpoint(),
            'token_endpoint' => McpUrls::tokenEndpoint(),
            'registration_endpoint' => McpUrls::registrationEndpoint(),
            'scopes_supported' => [config('mcp.scope')],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'code_challenge_methods_supported' => ['S256'],
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
