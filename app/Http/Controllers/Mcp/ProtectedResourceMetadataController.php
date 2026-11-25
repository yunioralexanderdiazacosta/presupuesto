<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Support\Mcp\McpUrls;
use Illuminate\Http\JsonResponse;

/** RFC 9728: indica a los asistentes qué servidor de autorización protege /mcp. */
class ProtectedResourceMetadataController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'resource' => McpUrls::server(),
            'authorization_servers' => [McpUrls::base()],
            'scopes_supported' => [config('mcp.scope')],
            'bearer_methods_supported' => ['header'],
            'resource_name' => config('mcp.name') . ' (solo lectura)',
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
