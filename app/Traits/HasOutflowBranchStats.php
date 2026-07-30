<?php

namespace App\Traits;

use App\Models\Outflow;

trait HasOutflowBranchStats
{
    /**
     * Devuelve el consumo (monto = cantidad x precio unitario) agrupado por
     * sucursal + nivel1 + nivel2 + nivel3. Mismo criterio que "Total Neto
     * Salidas" / cards por sucursal de Outflows.vue (quantity * unit_price,
     * sin ajuste por NC financiera), para que los montos siempre cuadren.
     */
    public function getConsumoPorSucursal($team_id, $season_id)
    {
        $outflows = Outflow::with([
            'invoiceProduct.branch:id,name',
            'creditDebitNoteItem.branch:id,name',
            'fuelOutflow.invoiceProduct.branch:id,name',
            'level3.level2.level1',
        ])
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->get();

        $result = [];

        foreach ($outflows as $outflow) {
            $unitPrice = $outflow->invoiceProduct
                ? ($outflow->invoiceProduct->unit_price ?? 0)
                : ($outflow->creditDebitNoteItem
                    ? ($outflow->creditDebitNoteItem->unit_price ?? 0)
                    : ($outflow->fuelOutflow?->invoiceProduct?->unit_price ?? 0));

            $branchId = $outflow->invoiceProduct
                ? $outflow->invoiceProduct->branch_id
                : ($outflow->creditDebitNoteItem
                    ? $outflow->creditDebitNoteItem->branch_id
                    : $outflow->fuelOutflow?->invoiceProduct?->branch_id);

            $branchName = $outflow->invoiceProduct
                ? $outflow->invoiceProduct->branch?->name
                : ($outflow->creditDebitNoteItem
                    ? $outflow->creditDebitNoteItem->branch?->name
                    : $outflow->fuelOutflow?->invoiceProduct?->branch?->name);

            $amount = ($outflow->quantity ?? 0) * $unitPrice;
            if ($amount == 0) {
                continue;
            }

            $level1 = $outflow->level3?->level2?->level1;
            $level2 = $outflow->level3?->level2;
            $level3 = $outflow->level3;

            $key = ($branchId ?? 'null') . '-' . ($level1->id ?? 'null') . '-' . ($level2->id ?? 'null') . '-' . ($level3->id ?? 'null');

            if (!isset($result[$key])) {
                $result[$key] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName ?? 'Sin sucursal',
                    'level1_id' => $level1->id ?? null,
                    'level1_name' => $level1->name ?? 'Sin Clasificar',
                    'level2_id' => $level2->id ?? null,
                    'level2_name' => $level2->name ?? 'Sin Clasificar',
                    'level3_id' => $level3->id ?? null,
                    'level3_name' => $level3->name ?? 'Sin Clasificar',
                    'amount' => 0,
                ];
            }

            $result[$key]['amount'] += $amount;
        }

        return array_values(array_map(function ($row) {
            $row['amount'] = round($row['amount'], 2);
            return $row;
        }, $result));
    }
}
