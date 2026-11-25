<?php

namespace App\Services\Mcp\Tools;

use App\Services\Mcp\InvoiceDebtReader;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;

class ListInvoicesTool extends McpTool
{
    public function name(): string
    {
        return 'list_invoices';
    }

    public function title(): string
    {
        return 'Listar facturas';
    }

    public function description(): string
    {
        return 'Lista las facturas de una temporada con su estado de pago, saldo y vencimiento (montos en CLP). '
            . 'Permite filtrar por estado (pending, partial, paid, annulled, overdue o unpaid = pendientes + parciales), '
            . 'proveedor, razón social, texto, rango de fechas de emisión o de vencimiento. '
            . 'Devuelve total_count con todas las coincidencias y has_more cuando hay más resultados que el límite.';
    }

    protected function properties(): array
    {
        return [
            'season_id' => ['type' => 'integer', 'description' => 'ID de la temporada. Si se omite se usa la predeterminada.'],
            'status' => ['type' => 'string', 'enum' => ['pending', 'partial', 'paid', 'annulled', 'overdue', 'unpaid'], 'description' => 'Estado de pago.'],
            'payment_type' => ['type' => 'string', 'enum' => ['credit', 'cash', 'all'], 'description' => 'Por defecto: credit.'],
            'supplier' => ['type' => 'string', 'description' => 'Nombre o RUT (parcial) del proveedor.'],
            'company_reason' => ['type' => 'string', 'description' => 'Razón social (nombre parcial).'],
            'search' => ['type' => 'string', 'description' => 'Texto a buscar en el número de documento o en el nombre del proveedor.'],
            'date_from' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha de emisión desde (YYYY-MM-DD).'],
            'date_to' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha de emisión hasta (YYYY-MM-DD).'],
            'due_from' => ['type' => 'string', 'format' => 'date', 'description' => 'Vencimiento desde (YYYY-MM-DD).'],
            'due_to' => ['type' => 'string', 'format' => 'date', 'description' => 'Vencimiento hasta (YYYY-MM-DD).'],
            'sort' => ['type' => 'string', 'enum' => ['due_date', 'date', 'balance'], 'description' => 'Orden: due_date (por defecto, lo no resuelto primero), date (más recientes primero) o balance (mayor saldo primero).'],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => (int) config('mcp.max_results'), 'description' => 'Máximo de filas a devolver.'],
            'offset' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Filas a omitir (paginación).'],
        ];
    }

    protected function rules(): array
    {
        return [
            'season_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,partial,paid,annulled,overdue,unpaid'],
            'payment_type' => ['nullable', 'in:credit,cash,all'],
            'supplier' => ['nullable', 'string', 'max:100'],
            'company_reason' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:due_date,date,balance'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $season = $context->season($arguments['season_id'] ?? null);
        $limit = $context->limit($arguments['limit'] ?? null);
        $offset = (int) ($arguments['offset'] ?? 0);

        $rows = (new InvoiceDebtReader($context->teamId(), $season->id))->rows($arguments);

        if (! empty($arguments['status'])) {
            $rows = $rows->filter(fn ($row) => InvoiceDebtReader::matchesStatus($row, $arguments['status']));
        }

        $rows = match ($arguments['sort'] ?? 'due_date') {
            'date' => $rows->sortByDesc(fn ($r) => (string) $r['invoice']->date),
            'balance' => $rows->sortByDesc(fn ($r) => $r['debt']['balance']),
            default => $rows->sortBy([
                fn ($a, $b) => (int) in_array(InvoiceDebtReader::status($a), ['paid', 'annulled'], true)
                    <=> (int) in_array(InvoiceDebtReader::status($b), ['paid', 'annulled'], true),
                fn ($a, $b) => strcmp((string) $a['invoice']->due_date, (string) $b['invoice']->due_date),
            ]),
        };

        $total = $rows->count();

        return [
            'season' => ['id' => $season->id, 'name' => $season->name],
            'currency' => 'CLP',
            'total_count' => $total,
            'total_balance' => $rows->sum(fn ($r) => $r['debt']['balance']),
            'has_more' => $total > $offset + $limit,
            'invoices' => $rows->slice($offset, $limit)->map(fn ($r) => InvoiceDebtReader::summaryRow($r))->values()->all(),
        ];
    }
}
