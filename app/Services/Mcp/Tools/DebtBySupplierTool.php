<?php

namespace App\Services\Mcp\Tools;

use App\Services\Mcp\InvoiceDebtReader;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;

class DebtBySupplierTool extends McpTool
{
    public function name(): string
    {
        return 'get_debt_by_supplier';
    }

    public function title(): string
    {
        return 'Deuda por proveedor';
    }

    public function description(): string
    {
        return 'Saldo por pagar agrupado por proveedor en una temporada (montos en CLP), ordenado de mayor a menor deuda, '
            . 'con el saldo vencido y el próximo vencimiento de cada uno. Solo incluye facturas con saldo pendiente.';
    }

    protected function properties(): array
    {
        return [
            'season_id' => ['type' => 'integer', 'description' => 'ID de la temporada. Si se omite se usa la predeterminada.'],
            'payment_type' => ['type' => 'string', 'enum' => ['credit', 'cash', 'all'], 'description' => 'Por defecto: credit.'],
            'only_overdue' => ['type' => 'boolean', 'description' => 'Si es true, considera solo facturas vencidas.'],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => (int) config('mcp.max_results'), 'description' => 'Máximo de proveedores a devolver.'],
        ];
    }

    protected function rules(): array
    {
        return [
            'season_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'in:credit,cash,all'],
            'only_overdue' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $season = $context->season($arguments['season_id'] ?? null);
        $limit = $context->limit($arguments['limit'] ?? null);
        $onlyOverdue = (bool) ($arguments['only_overdue'] ?? false);

        $rows = (new InvoiceDebtReader($context->teamId(), $season->id))
            ->rows(['payment_type' => $arguments['payment_type'] ?? 'credit'])
            ->filter(fn ($r) => InvoiceDebtReader::matchesStatus($r, 'unpaid') && $r['debt']['balance'] > 0)
            ->filter(fn ($r) => ! $onlyOverdue || $r['overdue']);

        $suppliers = $rows
            ->groupBy(fn ($r) => $r['invoice']->supplier_id ?? 0)
            ->map(function ($group) {
                $supplier = $group->first()['invoice']->supplier;

                return [
                    'supplier_id' => $supplier?->id,
                    'supplier' => $supplier?->name ?? 'Sin proveedor',
                    'rut' => $supplier?->rut,
                    'unpaid_invoices' => $group->count(),
                    'balance' => $group->sum(fn ($r) => $r['debt']['balance']),
                    'overdue_balance' => $group->where('overdue', true)->sum(fn ($r) => $r['debt']['balance']),
                    'next_due_date' => $group->pluck('invoice.due_date')->filter()->sort()->first(),
                ];
            })
            ->sortByDesc('balance')
            ->values();

        return [
            'season' => ['id' => $season->id, 'name' => $season->name],
            'currency' => 'CLP',
            'suppliers_with_debt' => $suppliers->count(),
            'total_balance' => $suppliers->sum('balance'),
            'suppliers' => $suppliers->take($limit)->all(),
        ];
    }
}
