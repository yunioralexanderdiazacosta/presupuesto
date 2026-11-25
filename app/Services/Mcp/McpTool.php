<?php

namespace App\Services\Mcp;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Herramienta MCP de solo lectura. Las implementaciones nunca deben modificar datos.
 */
abstract class McpTool
{
    /** Módulo (config/modules.php) que el usuario debe tener habilitado para usar la herramienta. */
    public const MODULE = 'invoice-payments.index';

    abstract public function name(): string;

    abstract public function title(): string;

    abstract public function description(): string;

    /** Propiedades JSON Schema de los argumentos. */
    abstract protected function properties(): array;

    /** Reglas de validación de Laravel para los argumentos. */
    abstract protected function rules(): array;

    /** Argumentos requeridos en el JSON Schema. */
    protected function required(): array
    {
        return [];
    }

    abstract protected function execute(McpContext $context, array $arguments): array;

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'title' => $this->title(),
            'description' => $this->description(),
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object) $this->properties(),
                'required' => $this->required(),
                'additionalProperties' => false,
            ],
            'annotations' => [
                'title' => $this->title(),
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'idempotentHint' => true,
                'openWorldHint' => false,
            ],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function call(McpContext $context, array $arguments): array
    {
        $validated = Validator::make($arguments, $this->rules(), [], $this->attributeNames())->validate();

        return $this->execute($context, $validated);
    }

    /** Nombres legibles de los argumentos para los mensajes de validación. */
    protected function attributeNames(): array
    {
        return [];
    }
}
