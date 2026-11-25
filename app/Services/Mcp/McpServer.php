<?php

namespace App\Services\Mcp;

use App\Services\Mcp\Tools\DebtBySupplierTool;
use App\Services\Mcp\Tools\GetInvoiceTool;
use App\Services\Mcp\Tools\ListInvoicesTool;
use App\Services\Mcp\Tools\ListPaymentsTool;
use App\Services\Mcp\Tools\ListSeasonsTool;
use App\Services\Mcp\Tools\PaymentSummaryTool;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

/**
 * Servidor MCP (Model Context Protocol) sin estado sobre JSON-RPC 2.0. Solo expone herramientas de lectura.
 *
 * @see https://modelcontextprotocol.io/specification/2025-06-18/basic/transports#streamable-http
 */
class McpServer
{
    public const LATEST_PROTOCOL_VERSION = '2025-06-18';

    public const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    private const PARSE_ERROR = -32700;
    private const INVALID_REQUEST = -32600;
    private const METHOD_NOT_FOUND = -32601;
    private const INVALID_PARAMS = -32602;
    private const INTERNAL_ERROR = -32603;

    /** @var array<string, McpTool> */
    private array $tools = [];

    public function __construct(?array $tools = null)
    {
        foreach ($tools ?? self::defaultTools() as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /** @return McpTool[] */
    public static function defaultTools(): array
    {
        return [
            new ListSeasonsTool(),
            new PaymentSummaryTool(),
            new ListInvoicesTool(),
            new GetInvoiceTool(),
            new ListPaymentsTool(),
            new DebtBySupplierTool(),
        ];
    }

    public static function supportsProtocolVersion(?string $version): bool
    {
        return $version === null || in_array($version, self::PROTOCOL_VERSIONS, true);
    }

    /**
     * Procesa el cuerpo de una petición POST. Devuelve null cuando solo hubo notificaciones (HTTP 202).
     */
    public function handleBody(mixed $body, McpContext $context): ?array
    {
        if (! is_array($body) || $body === []) {
            return $this->error(null, self::INVALID_REQUEST, 'Solicitud JSON-RPC inválida.');
        }

        // Los lotes existen solo en versiones anteriores del protocolo.
        if (array_is_list($body)) {
            $responses = array_values(array_filter(array_map(fn ($message) => $this->handleMessage($message, $context), $body)));

            return $responses === [] ? null : $responses;
        }

        return $this->handleMessage($body, $context);
    }

    public function parseError(): array
    {
        return $this->error(null, self::PARSE_ERROR, 'JSON inválido.');
    }

    private function handleMessage(mixed $message, McpContext $context): ?array
    {
        if (! is_array($message) || ($message['jsonrpc'] ?? null) !== '2.0') {
            return $this->error(null, self::INVALID_REQUEST, 'Solicitud JSON-RPC inválida.');
        }

        // Respuestas del cliente o notificaciones (sin id): no requieren respuesta.
        if (! isset($message['method'])) {
            return null;
        }

        $hasId = array_key_exists('id', $message);
        $id = $message['id'] ?? null;

        if (! is_string($message['method']) || ($hasId && ! is_string($id) && ! is_int($id))) {
            return $this->error(null, self::INVALID_REQUEST, 'Solicitud JSON-RPC inválida.');
        }

        if (! $hasId) {
            return null;
        }

        $params = $message['params'] ?? [];

        if (! is_array($params)) {
            return $this->error($id, self::INVALID_PARAMS, 'params debe ser un objeto.');
        }

        try {
            return match ($message['method']) {
                'initialize' => $this->result($id, $this->initialize($params)),
                'ping' => $this->result($id, (object) []),
                'tools/list' => $this->result($id, ['tools' => $this->listTools($context)]),
                'tools/call' => $this->result($id, $this->callTool($params, $context)),
                default => $this->error($id, self::METHOD_NOT_FOUND, 'Método no soportado: ' . $message['method']),
            };
        } catch (InvalidArgumentException $e) {
            return $this->error($id, self::INVALID_PARAMS, $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->error($id, self::INTERNAL_ERROR, 'Error interno del servidor.');
        }
    }

    private function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::LATEST_PROTOCOL_VERSION,
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => config('mcp.name'), 'title' => config('mcp.name') . ' (solo lectura)', 'version' => '1.0.0'],
            'instructions' => 'Consulta de facturas y pagos a proveedores en modo solo lectura. Los montos están en pesos chilenos (CLP). '
                . 'Si no se indica season_id, las herramientas usan la temporada predeterminada de la empresa del usuario.',
        ];
    }

    private function listTools(McpContext $context): array
    {
        return collect($this->tools)
            ->filter(fn (McpTool $tool) => $this->canUse($context, $tool))
            ->map(fn (McpTool $tool) => $tool->definition())
            ->values()
            ->all();
    }

    private function callTool(array $params, McpContext $context): array
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! is_string($name) || ! isset($this->tools[$name])) {
            throw new InvalidArgumentException('Herramienta desconocida.');
        }

        if (! is_array($arguments)) {
            throw new InvalidArgumentException('arguments debe ser un objeto.');
        }

        $tool = $this->tools[$name];
        $startedAt = microtime(true);

        try {
            if (! $this->canUse($context, $tool)) {
                throw new InvalidArgumentException('Tu usuario no tiene acceso al módulo de facturas y pagos.');
            }

            $data = $tool->call($context, $arguments);
            $response = $this->toolResult($data, false);
            $outcome = 'ok';
        } catch (ValidationException $e) {
            $response = $this->toolResult(['error' => collect($e->errors())->flatten()->implode(' ')], true);
            $outcome = 'invalid_arguments';
        } catch (InvalidArgumentException $e) {
            $response = $this->toolResult(['error' => $e->getMessage()], true);
            $outcome = 'rejected';
        } catch (Throwable $e) {
            report($e);
            $response = $this->toolResult(['error' => 'No se pudo completar la consulta.'], true);
            $outcome = 'error';
        }

        // Auditoría: quién consultó qué (nombres de argumentos, no su contenido).
        Log::channel('mcp')->info('mcp.tool_call', [
            'user_id' => $context->user->id,
            'team_id' => $context->teamId(),
            'client_id' => $context->user->token()?->client_id,
            'tool' => $name,
            'arguments' => array_keys($arguments),
            'outcome' => $outcome,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }

    private function canUse(McpContext $context, McpTool $tool): bool
    {
        return ! ModuleAccess::isBlockedFor($context->user, $tool::MODULE);
    }

    private function toolResult(array $data, bool $isError): array
    {
        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR),
            ]],
            'isError' => $isError,
        ];
    }

    private function result(string|int|null $id, array|object $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error(string|int|null $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
