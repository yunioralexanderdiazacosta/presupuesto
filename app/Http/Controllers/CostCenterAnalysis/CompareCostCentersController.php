<?php

namespace App\Http\Controllers\CostCenterAnalysis;

use App\Http\Controllers\Controller;
use App\Models\AgrochemicalOutflow;
use App\Models\CostCenter;
use App\Models\FertilizerOutflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CompareCostCentersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->count() < 2 || $ids->count() > 4) {
            return response()->json(['message' => 'Debes seleccionar entre 2 y 4 cuarteles para comparar.'], 422);
        }

        $costCenters = CostCenter::with(['fruit:id,name', 'variety:id,name', 'branch:id,name'])
            ->whereIn('id', $ids)
            ->where('season_id', $seasonId)
            ->whereHas('season', fn($q) => $q->where('team_id', $teamId))
            ->get()
            ->keyBy('id');

        if ($costCenters->count() !== $ids->count()) {
            return response()->json(['message' => 'Uno o más cuarteles no son válidos para esta temporada.'], 422);
        }

        $result = $ids->map(function ($id) use ($costCenters, $teamId, $seasonId) {
            $cc = $costCenters[$id];
            $surface = (float) $cc->surface;

            return [
                'id' => $cc->id,
                'name' => $cc->name,
                'surface' => $surface,
                'fruit_name' => $cc->fruit->name ?? 'Sin frutal',
                'variety_name' => $cc->variety->name ?? 'Sin variedad',
                'branch_name' => $cc->branch->name ?? null,
                'kg' => $this->getKilos($teamId, $seasonId, $id, $surface),
                'calibre' => $this->getCalibre($teamId, $seasonId, $id),
                'costos' => $this->getCostosPorCategoria($teamId, $seasonId, $id, $surface),
                'agroquimicos' => $this->getAgroquimicosDetalle($teamId, $seasonId, $id, $surface),
                'fertilizantes' => $this->getFertilizantesDetalle($teamId, $seasonId, $id, $surface),
            ];
        })->values();

        return response()->json(['cost_centers' => $result]);
    }

    private function getKilos($teamId, $seasonId, $ccId, $surface)
    {
        $ccvIds = DB::table('cost_center_varieties')->where('cost_center_id', $ccId)->pluck('id');

        $total = (float) DB::table('production_dispatches')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', 'processed')
            ->whereIn('cost_center_variety_id', $ccvIds)
            ->sum('kg_dispatched');

        return [
            'total' => round($total, 2),
            'per_ha' => $surface > 0 ? round($total / $surface, 2) : 0,
        ];
    }

    private function getCalibre($teamId, $seasonId, $ccId)
    {
        $ccvIds = DB::table('cost_center_varieties')->where('cost_center_id', $ccId)->pluck('id');

        $dispatchIds = DB::table('production_dispatches')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', 'processed')
            ->whereIn('cost_center_variety_id', $ccvIds)
            ->pluck('id');

        $rows = DB::table('production_dispatch_items')
            ->whereIn('production_dispatch_id', $dispatchIds)
            ->where('classification_type', 'caliber')
            ->selectRaw('classification_value, SUM(kg) as kg')
            ->groupBy('classification_value')
            ->orderByDesc('kg')
            ->get();

        $total = (float) $rows->sum('kg');

        return $rows->map(fn($r) => [
            'value' => $r->classification_value,
            'kg' => round((float) $r->kg, 2),
            'pct' => $total > 0 ? round(((float) $r->kg / $total) * 100, 1) : 0,
        ])->values();
    }

    /**
     * Costo prorrateado por cuartel, agrupado por categoría (Level2: agroquímicos, fertilizantes,
     * insumos, servicios, mano de obra, etc.)
     */
    private function getCostosPorCategoria($teamId, $seasonId, $ccId, $surface)
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

        $rows = DB::table('outflows')
            ->join('outflow_cost_center', 'outflows.id', '=', 'outflow_cost_center.outflow_id')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', fn($j) => $j->on('outflows.id', '=', 'surface_totals.outflow_id'))
            ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->leftJoin('fuel_outflows', 'outflows.fuel_outflow_id', '=', 'fuel_outflows.id')
            ->leftJoin('invoice_products as fuel_invoice_products', 'fuel_outflows.invoice_product_id', '=', 'fuel_invoice_products.id')
            ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
            ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
            ->where('outflows.team_id', $teamId)
            ->where('outflows.season_id', $seasonId)
            ->where('cost_centers.id', $ccId)
            ->selectRaw("COALESCE(level2s.name, 'sin clasificar') as categoria, COALESCE(SUM($amountExpr), 0) as amount")
            ->groupBy('level2s.name')
            ->havingRaw("COALESCE(SUM($amountExpr), 0) <> 0")
            ->orderByDesc('amount')
            ->get();

        return $rows->map(fn($r) => [
            'categoria' => ucwords((string) $r->categoria),
            'total' => round((float) $r->amount, 2),
            'per_ha' => $surface > 0 ? round((float) $r->amount / $surface, 2) : 0,
        ])->values();
    }

    private function getAgroquimicosDetalle($teamId, $seasonId, $ccId, $surface)
    {
        $rows = AgrochemicalOutflow::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('cost_center_id', $ccId)
            ->with(['product.level3', 'product.unit'])
            ->get();

        return $this->buildProductDetail($rows, 'application_order_id', $surface);
    }

    private function getFertilizantesDetalle($teamId, $seasonId, $ccId, $surface)
    {
        $rows = FertilizerOutflow::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('cost_center_id', $ccId)
            ->with(['product.level3', 'product.unit'])
            ->get();

        return $this->buildProductDetail($rows, 'fertilizer_order_id', $surface);
    }

    /**
     * Agrupa registros de salida (agroquímicos o fertilizantes) por subfamilia (Level3) y producto,
     * calculando cantidad total, dosis por hectárea y número de aplicaciones.
     */
    private function buildProductDetail(Collection $rows, string $orderField, float $surface): Collection
    {
        $bySubfamily = $rows->groupBy(fn($r) => $r->product->level3->name ?? 'Sin subfamilia');

        return $bySubfamily->map(function (Collection $items, $subfamiliaName) use ($surface, $orderField) {
            $byProduct = $items->groupBy('product_id');

            $productos = $byProduct->map(function (Collection $productItems) use ($surface, $orderField) {
                $qty = (float) $productItems->sum('quantity');
                $unitName = $productItems->first()->product->unit->name ?? '';

                return [
                    'producto' => $productItems->first()->product->name ?? 'Sin nombre',
                    'unidad' => $unitName,
                    'cantidad_total' => round($qty, 2),
                    'dosis_ha' => $surface > 0 ? round($qty / $surface, 3) : 0,
                    'n_aplicaciones' => $productItems->pluck($orderField)->filter()->unique()->count(),
                ];
            })->values()->sortByDesc('cantidad_total')->values();

            $subQty = (float) $items->sum('quantity');

            return [
                'subfamilia' => $subfamiliaName,
                'cantidad_total' => round($subQty, 2),
                'dosis_ha' => $surface > 0 ? round($subQty / $surface, 3) : 0,
                'n_aplicaciones' => $items->pluck($orderField)->filter()->unique()->count(),
                'productos' => $productos,
            ];
        })->values()->sortByDesc('cantidad_total')->values();
    }
}
