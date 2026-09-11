<?php

namespace App\Http\Controllers\CostCenterAnalysis;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CostCenterAnalysisController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        if (!$seasonId) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una temporada activa.');
        }

        $costCenters = CostCenter::with(['fruit:id,name', 'variety:id,name', 'branch:id,name'])
            ->where('season_id', $seasonId)
            ->where('status', true)
            ->whereHas('season', fn($q) => $q->where('team_id', $teamId))
            ->orderBy('name')
            ->get();

        $ccIds = $costCenters->pluck('id');

        $costsByCC = $this->getCostosPorCuartel($teamId, $seasonId, $ccIds);
        $kilosByCC = $this->getKilosPorCuartel($teamId, $seasonId, $ccIds);

        $rows = $costCenters->map(function ($cc) use ($costsByCC, $kilosByCC) {
            $costo = (float) ($costsByCC[$cc->id] ?? 0);
            $kg = (float) ($kilosByCC[$cc->id] ?? 0);
            $surface = (float) $cc->surface;

            return [
                'id' => $cc->id,
                'name' => $cc->name,
                'surface' => $surface,
                'fruit_id' => $cc->fruit_id,
                'fruit_name' => $cc->fruit->name ?? 'Sin frutal',
                'variety_id' => $cc->variety_id,
                'variety_name' => $cc->variety->name ?? 'Sin variedad',
                'branch_name' => $cc->branch->name ?? null,
                'costo_total' => round($costo, 2),
                'costo_ha' => $surface > 0 ? round($costo / $surface, 2) : 0,
                'kg_total' => round($kg, 2),
                'kg_ha' => $surface > 0 ? round($kg / $surface, 2) : 0,
            ];
        })->values();

        // Grupos Frutal + Variedad para el selector de comparación
        $fruits = $costCenters->pluck('fruit')->filter()->unique('id')->map(fn($f) => ['value' => $f->id, 'label' => $f->name])->values();
        $varieties = $costCenters->pluck('variety')->filter()->unique('id')->map(fn($v) => ['value' => $v->id, 'label' => $v->name, 'fruit_id' => $v->fruit_id])->values();

        return Inertia::render('CostCenterAnalysis/Index', [
            'costCenters' => $rows,
            'fruits' => $fruits,
            'varieties' => $varieties,
        ]);
    }

    /**
     * Costo total prorrateado por cuartel (todas las categorías: agroquímicos, fertilizantes,
     * combustible, insumos, servicios, etc.), reutilizando el mismo criterio de prorrateo
     * por superficie que HasOutflowHectareStats.
     */
    private function getCostosPorCuartel($teamId, $seasonId, $ccIds)
    {
        $amountExpr = "
            CASE
                WHEN cost_centers.surface = 0 THEN
                    outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
                ELSE
                    (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) *
                    COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, fuel_invoice_products.unit_price, 0)
            END
        ";

        $surfaceTotalsSubquery = DB::table('outflow_cost_center')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
            ->groupBy('outflow_cost_center.outflow_id');

        return DB::table('outflows')
            ->join('outflow_cost_center', 'outflows.id', '=', 'outflow_cost_center.outflow_id')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', fn($j) => $j->on('outflows.id', '=', 'surface_totals.outflow_id'))
            ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->leftJoin('fuel_outflows', 'outflows.fuel_outflow_id', '=', 'fuel_outflows.id')
            ->leftJoin('invoice_products as fuel_invoice_products', 'fuel_outflows.invoice_product_id', '=', 'fuel_invoice_products.id')
            ->where('outflows.team_id', $teamId)
            ->where('outflows.season_id', $seasonId)
            ->whereIn('cost_centers.id', $ccIds)
            ->selectRaw("cost_centers.id as cc_id, COALESCE(SUM($amountExpr), 0) as amount")
            ->groupBy('cost_centers.id')
            ->pluck('amount', 'cc_id');
    }

    /**
     * Kilos despachados por cuartel, solo despachos ya procesados (los únicos con calibre real).
     */
    private function getKilosPorCuartel($teamId, $seasonId, $ccIds)
    {
        return DB::table('production_dispatches')
            ->join('cost_center_varieties', 'production_dispatches.cost_center_variety_id', '=', 'cost_center_varieties.id')
            ->where('production_dispatches.team_id', $teamId)
            ->where('production_dispatches.season_id', $seasonId)
            ->where('production_dispatches.status', 'processed')
            ->whereIn('cost_center_varieties.cost_center_id', $ccIds)
            ->selectRaw('cost_center_varieties.cost_center_id as cc_id, SUM(production_dispatches.kg_dispatched) as kg')
            ->groupBy('cost_center_varieties.cost_center_id')
            ->pluck('kg', 'cc_id');
    }
}
