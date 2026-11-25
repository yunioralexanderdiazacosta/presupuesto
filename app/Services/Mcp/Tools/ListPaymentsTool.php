<?php

namespace App\Services\Mcp\Tools;

use App\Models\InvoicePayment;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;

class ListPaymentsTool extends McpTool
{
    private const METHODS = ['transfer' => 1, 'cash' => 2, 'check' => 3];

    public function name(): string
    {
        return 'list_payments';
    }

    public function title(): string
    {
        return 'Listar pagos de facturas';
    }

    public function description(): string
    {
        return 'Lista los pagos realizados a facturas en una temporada (montos en CLP), con total pagado de todas las coincidencias. '
            . 'Permite filtrar por rango de fechas de pago, proveedor y medio de pago.';
    }

    protected function properties(): array
    {
        return [
            'season_id' => ['type' => 'integer', 'description' => 'ID de la temporada. Si se omite se usa la predeterminada.'],
            'date_from' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha de pago desde (YYYY-MM-DD).'],
            'date_to' => ['type' => 'string', 'format' => 'date', 'description' => 'Fecha de pago hasta (YYYY-MM-DD).'],
            'supplier' => ['type' => 'string', 'description' => 'Nombre o RUT (parcial) del proveedor de la factura.'],
            'method' => ['type' => 'string', 'enum' => array_keys(self::METHODS), 'description' => 'Medio de pago: transfer, cash o check.'],
            'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => (int) config('mcp.max_results'), 'description' => 'Máximo de filas a devolver.'],
            'offset' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Filas a omitir (paginación).'],
        ];
    }

    protected function rules(): array
    {
        return [
            'season_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'supplier' => ['nullable', 'string', 'max:100'],
            'method' => ['nullable', 'in:' . implode(',', array_keys(self::METHODS))],
            'limit' => ['nullable', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $season = $context->season($arguments['season_id'] ?? null);
        $limit = $context->limit($arguments['limit'] ?? null);
        $offset = (int) ($arguments['offset'] ?? 0);

        $query = InvoicePayment::query()
            ->where('invoice_payments.team_id', $context->teamId())
            ->where('invoice_payments.season_id', $season->id)
            ->when($arguments['date_from'] ?? null, fn ($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($arguments['date_to'] ?? null, fn ($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->when($arguments['method'] ?? null, fn ($q, $m) => $q->where('payment_method', self::METHODS[$m]))
            ->when($arguments['supplier'] ?? null, function ($q, $name) {
                $like = '%' . addcslashes(trim($name), '%_\\') . '%';
                $q->whereHas('invoice.supplier', fn ($sq) => $sq
                    ->where(fn ($s) => $s->where('name', 'like', $like)->orWhere('rut', 'like', $like)));
            });

        $total = (clone $query)->count();
        $totalAmount = (float) (clone $query)->sum('amount');

        $payments = $query
            ->with(['invoice:id,number_document,supplier_id', 'invoice.supplier:id,name,rut', 'bank:id,name'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->skip($offset)
            ->take($limit)
            ->get();

        return [
            'season' => ['id' => $season->id, 'name' => $season->name],
            'currency' => 'CLP',
            'total_count' => $total,
            'total_amount' => $totalAmount,
            'has_more' => $total > $offset + $limit,
            'payments' => $payments->map(fn (InvoicePayment $p) => [
                'id' => $p->id,
                'date' => $p->payment_date?->toDateString(),
                'amount' => (float) $p->amount,
                'method' => $p->payment_method_name,
                'bank' => $p->bank?->name,
                'transaction_number' => $p->transaction_number,
                'invoice_id' => $p->invoice_id,
                'invoice_document_number' => $p->invoice?->number_document,
                'supplier' => $p->invoice?->supplier?->name,
            ])->values()->all(),
        ];
    }
}
