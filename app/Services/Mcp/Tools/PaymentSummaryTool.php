<?php

namespace App\Services\Mcp\Tools;

use App\Services\Mcp\InvoiceDebtReader;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;

class PaymentSummaryTool extends McpTool
{
    public function name(): string
    {
        return 'get_payment_summary';
    }

    public function title(): string
    {
        return 'Resumen de pago de facturas';
    }

    public function description(): string
    {
        return 'Resumen de la deuda con proveedores de una temporada (montos en pesos chilenos, CLP): cantidad y monto de facturas '
            . 'pendientes, con pago parcial, pagadas, anuladas y vencidas, más el saldo total por pagar. '
            . 'Por defecto considera solo facturas a crédito, igual que la pantalla "Pago de Facturas".';
    }

    protected function properties(): array
    {
        return [
            'season_id' => ['type' => 'integer', 'description' => 'ID de la temporada. Si se omite se usa la predeterminada (ver list_seasons).'],
            'payment_type' => ['type' => 'string', 'enum' => ['credit', 'cash', 'all'], 'description' => 'Tipo de pago de las facturas. Por defecto: credit.'],
        ];
    }

    protected function rules(): array
    {
        return [
            'season_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'in:credit,cash,all'],
        ];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $season = $context->season($arguments['season_id'] ?? null);
        $paymentType = $arguments['payment_type'] ?? 'credit';

        $summary = [
            'total' => ['count' => 0, 'amount' => 0],
            'pending' => ['count' => 0, 'amount' => 0],
            'partial' => ['count' => 0, 'balance' => 0],
            'paid' => ['count' => 0, 'amount' => 0],
            'annulled' => ['count' => 0, 'amount' => 0],
            'overdue' => ['count' => 0, 'balance' => 0],
        ];

        foreach ((new InvoiceDebtReader($context->teamId(), $season->id))->rows(['payment_type' => $paymentType]) as $row) {
            $debt = $row['debt'];
            $status = InvoiceDebtReader::status($row);

            $summary['total']['count']++;
            $summary['total']['amount'] += $debt['total_invoice'];

            if ($status === 'annulled') {
                $summary['annulled']['count']++;
                $summary['annulled']['amount'] += $debt['total_invoice'];
            } elseif ($status === 'pending') {
                $summary['pending']['count']++;
                $summary['pending']['amount'] += $debt['owed'];
            } elseif ($status === 'partial') {
                $summary['partial']['count']++;
                $summary['partial']['balance'] += $debt['balance'];
            } else {
                $summary['paid']['count']++;
                $summary['paid']['amount'] += $debt['paid_via_expense_report'] ? $debt['owed'] : $debt['total_paid'];
            }

            if ($row['overdue']) {
                $summary['overdue']['count']++;
                $summary['overdue']['balance'] += $debt['balance'];
            }
        }

        return [
            'season' => ['id' => $season->id, 'name' => $season->name],
            'currency' => 'CLP',
            'payment_type' => $paymentType,
            'summary' => $summary,
            'outstanding_balance' => $summary['pending']['amount'] + $summary['partial']['balance'],
        ];
    }
}
