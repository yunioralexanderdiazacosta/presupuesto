<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasOutflowBranchStats
{
    /**
     * Expresión SQL del monto prorrateado por superficie cuando un consumo se
     * reparte en varios centros de costo. Si no tiene CC asignado, va completo
     * a "Sin sucursal" (cost_centers.id IS NULL tras el LEFT JOIN).
     */
    private function branchProratedAmountExpression()
    {
        return "
            CASE
                WHEN cost_centers.id IS NULL THEN
                    outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
                WHEN cost_centers.surface = 0 THEN
                    outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
                ELSE
                    (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) *
                    COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
            END
        ";
    }

    /**
     * Devuelve el consumo (monto prorrateado por superficie) agrupado por
     * sucursal DEL CENTRO DE COSTO (no la de la línea de factura) + nivel1 +
     * nivel2 + nivel3. Un consumo repartido en varios CC de distinta sucursal
     * se divide proporcionalmente a la superficie de cada CC (mismo criterio
     * que "Consumo por Hectárea").
     */
    public function getConsumoPorSucursal($team_id, $season_id)
    {
        $amountExpr = $this->branchProratedAmountExpression();

        $surfaceTotalsSubquery = DB::table('outflow_cost_center')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
            ->groupBy('outflow_cost_center.outflow_id');

        $rows = DB::table('outflows')
            ->leftJoin('outflow_cost_center', 'outflows.id', '=', 'outflow_cost_center.outflow_id')
            ->leftJoin('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', function ($join) {
                $join->on('outflows.id', '=', 'surface_totals.outflow_id');
            })
            ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->leftJoin('fuel_outflows', 'outflows.fuel_outflow_id', '=', 'fuel_outflows.id')
            ->leftJoin('invoice_products as fuel_invoice_products', 'fuel_outflows.invoice_product_id', '=', 'fuel_invoice_products.id')
            ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
            ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
            ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
            ->leftJoin('branches', 'cost_centers.branch_id', '=', 'branches.id')
            ->where('outflows.team_id', $team_id)
            ->where('outflows.season_id', $season_id)
            ->selectRaw("
                cost_centers.branch_id,
                COALESCE(branches.name, 'Sin sucursal') as branch_name,
                COALESCE(level1s.id, 0) as level1_id,
                COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                COALESCE(level2s.id, 0) as level2_id,
                COALESCE(level2s.name, 'Sin Clasificar') as level2_name,
                COALESCE(level3s.id, 0) as level3_id,
                COALESCE(level3s.name, 'Sin Clasificar') as level3_name,
                COALESCE(SUM({$amountExpr}), 0) as amount
            ")
            ->groupBy(
                'cost_centers.branch_id',
                'branches.name',
                'level1s.id',
                'level1s.name',
                'level2s.id',
                'level2s.name',
                'level3s.id',
                'level3s.name'
            )
            ->havingRaw("COALESCE(SUM({$amountExpr}), 0) <> 0")
            ->get();

        return $rows->map(fn($r) => [
            'branch_id'   => $r->branch_id,
            'branch_name' => $r->branch_name,
            'level1_id'   => $r->level1_id ?: null,
            'level1_name' => $r->level1_name,
            'level2_id'   => $r->level2_id ?: null,
            'level2_name' => $r->level2_name,
            'level3_id'   => $r->level3_id ?: null,
            'level3_name' => $r->level3_name,
            'amount'      => round((float) $r->amount, 2),
        ])->toArray();
    }
}

