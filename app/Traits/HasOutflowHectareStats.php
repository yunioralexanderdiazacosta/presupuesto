<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasOutflowHectareStats
{
    /**
     * Expresión SQL del monto prorrateado por superficie cuando un consumo se
     * reparte en varios centros de costo (mismo criterio que HectareDashboardController).
     */
    private function hectareProratedAmountExpression()
    {
        return "
            CASE
                WHEN cost_centers.surface = 0 THEN
                    outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
                ELSE
                    (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) *
                    COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
            END
        ";
    }

    /**
     * Consumo prorrateado agrupado por sucursal DEL CENTRO DE COSTO (no la de compra),
     * estado de desarrollo y nivel1/nivel2/nivel3, para poder dividir por hectárea.
     */
    public function getConsumoPorHectarea($team_id, $season_id)
    {
        $amountExpr = $this->hectareProratedAmountExpression();

        $surfaceTotalsSubquery = DB::table('outflow_cost_center')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
            ->groupBy('outflow_cost_center.outflow_id');

        $rows = DB::table('outflows')
            ->join('outflow_cost_center', 'outflows.id', '=', 'outflow_cost_center.outflow_id')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
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
            ->leftJoin('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
            ->where('outflows.team_id', $team_id)
            ->where('outflows.season_id', $season_id)
            ->selectRaw("
                COALESCE(branches.name, 'Sin sucursal') as branch_name,
                COALESCE(cost_centers.development_state_id, 0) as development_state_id,
                COALESCE(development_states.name, 'Sin Estado') as development_state_name,
                COALESCE(level1s.id, 0) as level1_id,
                COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                COALESCE(level2s.id, 0) as level2_id,
                COALESCE(level2s.name, 'Sin Clasificar') as level2_name,
                COALESCE(level3s.id, 0) as level3_id,
                COALESCE(level3s.name, 'Sin Clasificar') as level3_name,
                COALESCE(SUM({$amountExpr}), 0) as amount
            ")
            ->groupBy(
                'branches.name',
                'cost_centers.development_state_id',
                'development_states.name',
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
            'branch_name'             => $r->branch_name,
            'development_state_id'    => (int) $r->development_state_id,
            'development_state_name'  => $r->development_state_name,
            'level1_id'               => $r->level1_id ?: null,
            'level1_name'             => $r->level1_name,
            'level2_id'               => $r->level2_id ?: null,
            'level2_name'             => $r->level2_name,
            'level3_id'               => $r->level3_id ?: null,
            'level3_name'             => $r->level3_name,
            'amount'                  => round((float) $r->amount, 2),
        ])->toArray();
    }

    /**
     * Superficie (ha) agrupada por sucursal del centro de costo + estado de
     * desarrollo. Solo considera centros de costo con al menos un consumo en
     * la temporada (mismo criterio que HectareDashboardController), para que
     * el denominador del $/ha sea consistente con el resto del sistema.
     */
    public function getSuperficiePorSucursalEstado($team_id, $season_id)
    {
        $rows = DB::table('cost_centers')
            ->leftJoin('branches', 'cost_centers.branch_id', '=', 'branches.id')
            ->leftJoin('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
            ->where('cost_centers.season_id', $season_id)
            ->whereIn('cost_centers.id', function ($q) use ($team_id, $season_id) {
                $q->select('cost_center_id')
                    ->from('outflow_cost_center')
                    ->join('outflows', 'outflow_cost_center.outflow_id', '=', 'outflows.id')
                    ->where('outflows.team_id', $team_id)
                    ->where('outflows.season_id', $season_id);
            })
            ->selectRaw("
                COALESCE(branches.name, 'Sin sucursal') as branch_name,
                COALESCE(cost_centers.development_state_id, 0) as development_state_id,
                SUM(cost_centers.surface) as surface
            ")
            ->groupBy('branches.name', 'cost_centers.development_state_id')
            ->get();

        return $rows->map(fn($r) => [
            'branch_name'          => $r->branch_name,
            'development_state_id' => (int) $r->development_state_id,
            'surface'              => round((float) $r->surface, 2),
        ])->toArray();
    }

    /**
     * Estados de desarrollo presentes en los centros de costo de la temporada
     * (opciones para el filtro del frontend).
     */
    public function getDevelopmentStatesForSeason($season_id)
    {
        return DB::table('cost_centers')
            ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
            ->where('cost_centers.season_id', $season_id)
            ->select('development_states.id', 'development_states.name')
            ->distinct()
            ->orderBy('development_states.name')
            ->get()
            ->map(fn($item) => ['value' => $item->id, 'label' => $item->name])
            ->toArray();
    }
}
