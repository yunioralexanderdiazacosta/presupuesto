<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Endpoint MCP (transporte HTTP "streamable", respuestas JSON sin sesión). */
class McpServerController extends Controller
{
    private const MAX_BODY_BYTES = 262144;

    /** El servidor no usa sesiones ni flujos SSE: GET y DELETE se rechazan explícitamente. */
    public function methodNotAllowed(): JsonResponse
    {
        return response()->json(['error' => 'method_not_allowed'], 405, ['Allow' => 'POST']);
    }

    public function __invoke(Request $request, McpServer $server): JsonResponse|Response
    {
        $version = $request->header('MCP-Protocol-Version');

        if (! McpServer::supportsProtocolVersion($version)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => ['code' => -32600, 'message' => 'Versión de protocolo no soportada: ' . $version],
            ], 400);
        }

        $content = $request->getContent();

        if (strlen($content) > self::MAX_BODY_BYTES) {
            return response()->json(['error' => 'payload_too_large'], 413);
        }

        $body = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json($server->parseError(), 400);
        }

        $response = $server->handleBody($body, new McpContext($request->user()));

        if ($response === null) {
            return response()->noContent(202);
        }

        return response()->json($response, 200, ['Cache-Control' => 'no-store'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
