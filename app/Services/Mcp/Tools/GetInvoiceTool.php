<?php

namespace App\Services\Mcp\Tools;

use App\Models\InvoiceProduct;
use App\Services\Mcp\InvoiceDebtReader;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;
use Illuminate\Validation\ValidationException;

class GetInvoiceTool extends McpTool
{
    private const MAX_LINES = 50;

    public function name(): string
    {
        return 'get_invoice';
    }

    public function title(): string
    {
        return 'Detalle de factura';
    }

    public function description(): string
    {
        return 'Detalle de una factura de la temporada: totales, saldo, pagos realizados, notas de crédito/débito y líneas de productos '
            . '(montos en CLP). Se identifica por invoice_id o por document_number (con supplier para desambiguar).';
    }

    protected function properties(): array
    {
        return [
            'invoice_id' => ['type' => 'integer', 'description' => 'ID de la factura (campo id devuelto por list_invoices).'],
            'document_number' => ['type' => 'string', 'description' => 'Número de documento de la factura.'],
            'supplier' => ['type' => 'string', 'description' => 'Nombre o RUT (parcial) del proveedor, útil si el número se repite entre proveedores.'],
            'season_id' => ['type' => 'integer', 'description' => 'ID de la temporada. Si se omite se usa la predeterminada.'],
        ];
    }

    protected function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'integer', 'required_without:document_number'],
            'document_number' => ['nullable', 'string', 'max:50', 'required_without:invoice_id'],
            'supplier' => ['nullable', 'string', 'max:100'],
            'season_id' => ['nullable', 'integer'],
        ];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $season = $context->season($arguments['season_id'] ?? null);
        $reader = new InvoiceDebtReader($context->teamId(), $season->id);

        $rows = $reader->rows([
            'payment_type' => 'all',
            'invoice_id' => $arguments['invoice_id'] ?? null,
            'supplier' => $arguments['supplier'] ?? null,
        ]);

        if (! empty($arguments['document_number'])) {
            $rows = $rows->filter(fn ($r) => (string) $r['invoice']->number_document === (string) $arguments['document_number']);
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['invoice' => 'No se encontró la factura en la temporada indicada.']);
        }

        if ($rows->count() > 1) {
            throw ValidationException::withMessages([
                'invoice' => 'Hay varias facturas con ese número. Indique supplier o use invoice_id: '
                    . $rows->map(fn ($r) => '#' . $r['invoice']->id . ' (' . ($r['invoice']->supplier?->name ?? 'sin proveedor') . ')')->implode(', '),
            ]);
        }

        $row = $rows->first();
        $invoice = $row['invoice'];
        $debt = $row['debt'];

        $invoice->load(['payments.bank:id,name', 'invoiceProducts.product:id,name']);

        return [
            'season' => ['id' => $season->id, 'name' => $season->name],
            'currency' => 'CLP',
            'invoice' => InvoiceDebtReader::summaryRow($row) + [
                'net_total' => $debt['total_neto'],
                'vat' => $debt['iva'],
                'credit_notes_total' => $debt['credit_total'],
                'debit_notes_total' => $debt['debit_total'],
                'paid_via_expense_report' => $debt['paid_via_expense_report'],
            ],
            'payments' => $invoice->payments->sortBy('payment_date')->map(fn ($p) => [
                'id' => $p->id,
                'date' => $p->payment_date?->toDateString(),
                'amount' => (float) $p->amount,
                'method' => $p->payment_method_name,
                'bank' => $p->bank?->name,
                'transaction_number' => $p->transaction_number,
            ])->values()->all(),
            'credit_debit_notes' => $debt['notes']->map(fn ($n) => [
                'type' => $n['type'],
                'number' => $n['number'],
                'total' => $n['total'],
                'is_annulment' => $n['is_annulment'],
            ])->values()->all(),
            'lines_count' => $invoice->invoiceProducts->count(),
            'lines' => $invoice->invoiceProducts->take(self::MAX_LINES)->map(fn (InvoiceProduct $l) => [
                'product' => $l->product?->name,
                'quantity' => (float) $l->amount,
                'unit_price' => (float) $l->unit_price,
                'subtotal' => round($l->amount * $l->unit_price),
                'is_exempt' => (bool) $l->is_exento,
            ])->values()->all(),
        ];
    }
}
