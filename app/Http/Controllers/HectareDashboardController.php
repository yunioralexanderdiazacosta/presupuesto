<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HectareDashboardController extends Controller
{
    public function index(Request $request)
    {
        $season_id = session('season_id');
        $team_id = Auth::user()->team_id;

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        return Inertia::render('HectareDashboard', [
            'surfaceByDevelopmentState' => $this->getSurfaceByDevelopmentState($season_id, $team_id),
            'surfaceByFruit'           => $this->getSurfaceByFruit($season_id, $team_id),
            'costPerHaByDevState'      => $this->getCostPerHaByDevelopmentState($season_id, $team_id),
            'costPerHaByFruit'         => $this->getCostPerHaByFruit($season_id, $team_id),
            'costPerHaByFruitDevState' => $this->getCostPerHaByFruitAndDevState($season_id, $team_id),
            'costPerHaByLevel1'        => $this->getCostPerHaByLevel1($season_id, $team_id),
            'costPerHaByLevel2'        => $this->getCostPerHaByLevel2($season_id, $team_id),
            'monthlyCostPerHa'         => $this->getMonthlyCostPerHa($season_id, $team_id),
            'surfaceByVariety'         => $this->getSurfaceByVariety($season_id, $team_id),
            'costPerHaByVariety'       => $this->getCostPerHaByVariety($season_id, $team_id),
            'costByVarietyLevel2'      => $this->getCostByVarietyLevel2($season_id, $team_id),
            'varietyDevStates'         => $this->getVarietyDevStates($season_id, $team_id),
            'costPerHaByCC'            => $this->getCostPerHaByCC($season_id, $team_id),
            'costByCCLevel2'           => $this->getCostByCCLevel2($season_id, $team_id),
        ]);
    }

    /**
     * Superficie total por estado de desarrollo
     */
    private function getSurfaceByDevelopmentState($season_id, $team_id)
    {
        try {
            $results = CostCenter::where('season_id', $season_id)
                ->whereHas('agrochemicals', function () {}, '>=', 0) // solo CC del team via outflows
                ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
                ->whereIn('cost_centers.id', function ($query) use ($team_id, $season_id) {
                    $query->select('cost_center_id')
                        ->from('outflow_cost_center')
                        ->join('outflows', 'outflow_cost_center.outflow_id', '=', 'outflows.id')
                        ->where('outflows.team_id', $team_id)
                        ->where('outflows.season_id', $season_id);
                })
                ->orWhere(function ($q) use ($season_id) {
                    $q->where('cost_centers.season_id', $season_id);
                })
                ->select(
                    'development_states.name as state_name',
                    DB::raw('SUM(cost_centers.surface) as total_surface'),
                    DB::raw('COUNT(cost_centers.id) as count_cc')
                )
                ->where('cost_centers.season_id', $season_id)
                ->groupBy('development_states.id', 'development_states.name')
                ->orderBy('total_surface', 'desc')
                ->get();

            return $results->map(fn($item) => [
                'name'    => $item->state_name,
                'surface' => floatval($item->total_surface),
                'count'   => intval($item->count_cc),
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getSurfaceByDevelopmentState: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Superficie total por frutal
     */
    private function getSurfaceByFruit($season_id, $team_id)
    {
        try {
            $results = DB::table('cost_centers')
                ->join('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->where('cost_centers.season_id', $season_id)
                ->select(
                    'fruits.id as fruit_id',
                    'fruits.name as fruit_name',
                    DB::raw('SUM(cost_centers.surface) as total_surface'),
                    DB::raw('COUNT(cost_centers.id) as count_cc')
                )
                ->groupBy('fruits.id', 'fruits.name')
                ->orderBy('total_surface', 'desc')
                ->get();

            return $results->map(fn($item) => [
                'fruit_id' => intval($item->fruit_id),
                'name'     => $item->fruit_name,
                'surface'  => floatval($item->total_surface),
                'count'    => intval($item->count_cc),
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getSurfaceByFruit: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Subconsulta reutilizable: superficie total por outflow
     */
    private function surfaceTotalsSubquery()
    {
        return DB::table('outflow_cost_center')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
            ->groupBy('outflow_cost_center.outflow_id');
    }

    /**
     * Expresión SQL del monto prorrateado por superficie
     */
    private function proratedAmountExpression()
    {
        return "
            CASE 
                WHEN cost_centers.surface = 0 THEN 
                    outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                ELSE 
                    (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) * 
                    COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
            END
        ";
    }

    /**
     * Query base con todos los joins necesarios para prorrateo
     */
    private function baseOutflowQuery($season_id, $team_id, $excludeInvestments = true)
    {
        $query = DB::table('outflows')
            ->join('outflow_cost_center', 'outflows.id', '=', 'outflow_cost_center.outflow_id')
            ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
            ->leftJoinSub($this->surfaceTotalsSubquery(), 'surface_totals', function ($join) {
                $join->on('outflows.id', '=', 'surface_totals.outflow_id');
            })
            ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->leftJoin('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->leftJoin('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('outflows.season_id', $season_id)
            ->where('outflows.team_id', $team_id);

        if ($excludeInvestments) {
            $query->leftJoin('operations', 'outflows.operation_id', '=', 'operations.id')
                ->where(function ($q) {
                    $q->whereNull('operations.name')
                        ->orWhereRaw('LOWER(operations.name) NOT LIKE ?', ['%inversion%']);
                });
        }

        return $query;
    }

    /**
     * Costo total y costo/ha por estado de desarrollo (sin inversiones)
     */
    private function getCostPerHaByDevelopmentState($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->selectRaw("
                    development_states.id as state_id,
                    development_states.name as state_name,
                    COALESCE(fruits.id, 0) as fruit_id,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('development_states.id', 'development_states.name', 'fruits.id')
                ->orderBy('total_cost', 'desc')
                ->get();

            // Obtener superficies por estado + frutal
            $surfaces = DB::table('cost_centers')
                ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
                ->where('cost_centers.season_id', $season_id)
                ->select(
                    'development_states.id as state_id',
                    'cost_centers.fruit_id',
                    DB::raw('SUM(cost_centers.surface) as total_surface')
                )
                ->groupBy('development_states.id', 'cost_centers.fruit_id')
                ->get()
                ->keyBy(fn($item) => $item->state_id . '||' . ($item->fruit_id ?? 0));

            return $results->map(function ($item) use ($surfaces) {
                $key = $item->state_id . '||' . ($item->fruit_id ?? 0);
                $surface = floatval($surfaces[$key]->total_surface ?? 0);
                $totalCost = floatval($item->total_cost);
                return [
                    'state_id'    => intval($item->state_id),
                    'fruit_id'    => intval($item->fruit_id ?? 0),
                    'name'        => $item->state_name,
                    'total_cost'  => $totalCost,
                    'surface'     => $surface,
                    'cost_per_ha' => $surface > 0 ? $totalCost / $surface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByDevelopmentState: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo total y costo/ha por frutal (sin inversiones)
     */
    private function getCostPerHaByFruit($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->selectRaw("
                    COALESCE(fruits.name, 'Sin Frutal') as fruit_name,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('fruits.id', 'fruits.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            // Obtener superficies por frutal
            $surfaces = DB::table('cost_centers')
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->where('cost_centers.season_id', $season_id)
                ->selectRaw("COALESCE(fruits.name, 'Sin Frutal') as fruit_name, SUM(cost_centers.surface) as total_surface")
                ->groupBy('fruits.id', 'fruits.name')
                ->pluck('total_surface', 'fruit_name');

            return $results->map(function ($item) use ($surfaces) {
                $surface = floatval($surfaces[$item->fruit_name] ?? 0);
                $totalCost = floatval($item->total_cost);
                return [
                    'name'        => $item->fruit_name,
                    'total_cost'  => $totalCost,
                    'surface'     => $surface,
                    'cost_per_ha' => $surface > 0 ? $totalCost / $surface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByFruit: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cruce: Costo/ha por Frutal × Estado de Desarrollo (sin inversiones)
     */
    private function getCostPerHaByFruitAndDevState($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
                ->selectRaw("
                    COALESCE(fruits.name, 'Sin Frutal') as fruit_name,
                    development_states.name as state_name,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('fruits.id', 'fruits.name', 'development_states.id', 'development_states.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            // Obtener superficies por frutal × estado
            $surfaces = DB::table('cost_centers')
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->join('development_states', 'cost_centers.development_state_id', '=', 'development_states.id')
                ->where('cost_centers.season_id', $season_id)
                ->selectRaw("
                    CONCAT(COALESCE(fruits.name, 'Sin Frutal'), '||', development_states.name) as combo_key,
                    SUM(cost_centers.surface) as total_surface
                ")
                ->groupBy('fruits.id', 'fruits.name', 'development_states.id', 'development_states.name')
                ->pluck('total_surface', 'combo_key');

            return $results->map(function ($item) use ($surfaces) {
                $key = $item->fruit_name . '||' . $item->state_name;
                $surface = floatval($surfaces[$key] ?? 0);
                $totalCost = floatval($item->total_cost);
                return [
                    'fruit'       => $item->fruit_name,
                    'state'       => $item->state_name,
                    'total_cost'  => $totalCost,
                    'surface'     => $surface,
                    'cost_per_ha' => $surface > 0 ? $totalCost / $surface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByFruitAndDevState: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo/ha por Level 1 (categoría de presupuesto) (sin inversiones)
     */
    private function getCostPerHaByLevel1($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            // Superficie total de CC que tienen outflows en la temporada
            $totalSurface = DB::table('cost_centers')
                ->where('cost_centers.season_id', $season_id)
                ->whereIn('cost_centers.id', function ($q) use ($team_id, $season_id) {
                    $q->select('cost_center_id')
                        ->from('outflow_cost_center')
                        ->join('outflows', 'outflow_cost_center.outflow_id', '=', 'outflows.id')
                        ->where('outflows.team_id', $team_id)
                        ->where('outflows.season_id', $season_id);
                })
                ->sum('surface');

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
                ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
                ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
                ->selectRaw("
                    COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                    COALESCE(cost_centers.development_state_id, 0) as state_id,
                    COALESCE(cost_centers.fruit_id, 0) as fruit_id,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('level1s.id', 'level1s.name', 'cost_centers.development_state_id', 'cost_centers.fruit_id')
                ->orderBy('total_cost', 'desc')
                ->get();

            return [
                'total_surface' => floatval($totalSurface),
                'data' => $results->map(function ($item) use ($totalSurface) {
                    $totalCost = floatval($item->total_cost);
                    return [
                        'name'        => $item->level1_name,
                        'state_id'    => intval($item->state_id),
                        'fruit_id'    => intval($item->fruit_id ?? 0),
                        'total_cost'  => $totalCost,
                        'cost_per_ha' => $totalSurface > 0 ? $totalCost / $totalSurface : 0,
                    ];
                })->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByLevel1: ' . $e->getMessage());
            return ['total_surface' => 0, 'data' => []];
        }
    }

    /**
     * Costo/ha agrupado por Level2 (con su Level1 padre)
     */
    private function getCostPerHaByLevel2($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $totalSurface = DB::table('cost_centers')
                ->where('cost_centers.season_id', $season_id)
                ->whereIn('cost_centers.id', function ($q) use ($team_id, $season_id) {
                    $q->select('cost_center_id')
                        ->from('outflow_cost_center')
                        ->join('outflows', 'outflow_cost_center.outflow_id', '=', 'outflows.id')
                        ->where('outflows.team_id', $team_id)
                        ->where('outflows.season_id', $season_id);
                })
                ->sum('surface');

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
                ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
                ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
                ->selectRaw("
                    COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                    COALESCE(level2s.name, 'Sin Clasificar') as level2_name,
                    COALESCE(level3s.name, 'Sin Clasificar') as level3_name,
                    COALESCE(cost_centers.development_state_id, 0) as state_id,
                    COALESCE(cost_centers.fruit_id, 0) as fruit_id,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('level1s.id', 'level1s.name', 'level2s.id', 'level2s.name', 'level3s.id', 'level3s.name', 'cost_centers.development_state_id', 'cost_centers.fruit_id')
                ->orderBy('level1s.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            return $results->map(function ($item) use ($totalSurface) {
                $totalCost = floatval($item->total_cost);
                return [
                    'level1'      => $item->level1_name,
                    'name'        => $item->level2_name,
                    'level3'      => $item->level3_name,
                    'state_id'    => intval($item->state_id),
                    'fruit_id'    => intval($item->fruit_id ?? 0),
                    'total_cost'  => $totalCost,
                    'cost_per_ha' => $totalSurface > 0 ? $totalCost / $totalSurface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByLevel2: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Evolución mensual del costo/ha acumulado (sin inversiones)
     */
    private function getMonthlyCostPerHa($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            // Obtener mes de inicio y año de la temporada
            $season = \App\Models\Season::where('id', $season_id)->first();
            $startMonth = $season ? $season->month_id : 1;

            // Deducir año de inicio desde el nombre de la temporada (ej: "temp 2025-2026" → 2025)
            $startYear = now()->year;
            if ($season && preg_match('/(\d{4})/', $season->name, $matches)) {
                $startYear = intval($matches[1]);
            }

            // Calcular rango de fechas válido para la temporada (12 meses)
            $dateFrom = sprintf('%04d-%02d-01', $startYear, $startMonth);
            $endMonth = (($startMonth - 1 + 11) % 12) + 1;
            $endYear = $startYear + ($startMonth + 11 > 12 ? 1 : 0);
            $dateTo = sprintf('%04d-%02d-31', $endYear, $endMonth);

            // Superficie total de CC con outflows
            $totalSurface = DB::table('cost_centers')
                ->where('cost_centers.season_id', $season_id)
                ->whereIn('cost_centers.id', function ($q) use ($team_id, $season_id) {
                    $q->select('cost_center_id')
                        ->from('outflow_cost_center')
                        ->join('outflows', 'outflow_cost_center.outflow_id', '=', 'outflows.id')
                        ->where('outflows.team_id', $team_id)
                        ->where('outflows.season_id', $season_id);
                })
                ->sum('surface');

            $dateExpr = "COALESCE(invoices.date, credit_debit_notes.date, outflows.date)";

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->whereRaw("{$dateExpr} BETWEEN ? AND ?", [$dateFrom, $dateTo])
                ->selectRaw("
                    DATE_FORMAT({$dateExpr}, '%Y-%m') as month,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Indexar costos por clave YYYY-MM
            $costByMonth = [];
            foreach ($results as $row) {
                $costByMonth[$row->month] = floatval($row->total_cost);
            }

            $monthNames = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
            ];

            // Generar los 12 meses de la temporada
            $months = [];
            $monthlyCosts = [];
            $cumulativeCosts = [];
            $cumulativeCostPerHa = [];
            $accumulated = 0;

            for ($i = 0; $i < 12; $i++) {
                $m = (($startMonth - 1 + $i) % 12) + 1;
                $y = $startYear + (($startMonth - 1 + $i) >= 12 ? 1 : 0);

                $key = sprintf('%04d-%02d', $y, $m);
                $label = $monthNames[$m] . ' ' . substr($y, 2, 2);
                $cost = $costByMonth[$key] ?? 0;
                $accumulated += $cost;

                $months[] = $label;
                $monthlyCosts[] = $cost;
                $cumulativeCosts[] = $accumulated;
                $cumulativeCostPerHa[] = $totalSurface > 0 ? $accumulated / $totalSurface : 0;
            }

            return [
                'labels'              => $months,
                'monthly_costs'       => $monthlyCosts,
                'cumulative_costs'    => $cumulativeCosts,
                'cumulative_per_ha'   => $cumulativeCostPerHa,
                'total_surface'       => floatval($totalSurface),
            ];
        } catch (\Exception $e) {
            Log::error('HectareDashboard getMonthlyCostPerHa: ' . $e->getMessage());
            return [
                'labels' => [], 'monthly_costs' => [],
                'cumulative_costs' => [], 'cumulative_per_ha' => [],
                'total_surface' => 0,
            ];
        }
    }

    /**
     * Superficie por variedad + estado de desarrollo (desde cost_center_varieties)
     */
    private function getSurfaceByVariety($season_id, $team_id)
    {
        try {
            $results = DB::table('cost_center_varieties')
                ->join('varieties', 'cost_center_varieties.variety_id', '=', 'varieties.id')
                ->leftJoin('development_states', 'cost_center_varieties.development_state_id', '=', 'development_states.id')
                ->where('cost_center_varieties.season_id', $season_id)
                ->where('cost_center_varieties.team_id', $team_id)
                ->select(
                    'varieties.name as variety_name',
                    DB::raw("COALESCE(development_states.name, 'Sin Estado') as state_name"),
                    DB::raw("COALESCE(cost_center_varieties.development_state_id, 0) as state_id"),
                    'cost_center_varieties.fruit_id',
                    DB::raw('SUM(cost_center_varieties.surface) as total_surface'),
                    DB::raw('COUNT(cost_center_varieties.id) as count_cc')
                )
                ->groupBy('varieties.id', 'varieties.name', 'cost_center_varieties.development_state_id', 'development_states.name', 'cost_center_varieties.fruit_id')
                ->orderBy('total_surface', 'desc')
                ->get();

            return $results->map(fn($item) => [
                'name'     => $item->variety_name,
                'state'    => $item->state_name,
                'state_id' => intval($item->state_id),
                'fruit_id' => intval($item->fruit_id ?? 0),
                'surface'  => floatval($item->total_surface),
                'count'    => intval($item->count_cc),
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getSurfaceByVariety: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo/ha por variedad + estado de desarrollo.
     */
    private function getCostPerHaByVariety($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $ccVarietySurfaceTotals = DB::table('cost_center_varieties')
                ->select('cost_center_id', DB::raw('SUM(surface) as total_variety_surface'))
                ->where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->groupBy('cost_center_id');

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->join('cost_center_varieties', 'cost_centers.id', '=', 'cost_center_varieties.cost_center_id')
                ->join('varieties', 'cost_center_varieties.variety_id', '=', 'varieties.id')
                ->leftJoin('development_states as ccv_dev_states', 'cost_center_varieties.development_state_id', '=', 'ccv_dev_states.id')
                ->leftJoinSub($ccVarietySurfaceTotals, 'ccv_totals', function ($join) {
                    $join->on('cost_centers.id', '=', 'ccv_totals.cost_center_id');
                })
                ->where('cost_center_varieties.season_id', $season_id)
                ->where('cost_center_varieties.team_id', $team_id)
                ->selectRaw("
                    varieties.id as variety_id,
                    varieties.name as variety_name,
                    COALESCE(cost_center_varieties.development_state_id, 0) as state_id,
                    COALESCE(ccv_dev_states.name, 'Sin Estado') as state_name,
                    COALESCE(cost_center_varieties.fruit_id, 0) as fruit_id,
                    COALESCE(SUM(
                        ({$amountExpr}) * 
                        cost_center_varieties.surface / NULLIF(ccv_totals.total_variety_surface, 0)
                    ), 0) as total_cost
                ")
                ->groupBy('varieties.id', 'varieties.name', 'cost_center_varieties.development_state_id', 'ccv_dev_states.name', 'cost_center_varieties.fruit_id')
                ->orderBy('total_cost', 'desc')
                ->get();

            // Superficies por variedad + estado + frutal
            $surfaces = DB::table('cost_center_varieties')
                ->join('varieties', 'cost_center_varieties.variety_id', '=', 'varieties.id')
                ->where('cost_center_varieties.season_id', $season_id)
                ->where('cost_center_varieties.team_id', $team_id)
                ->select(
                    'varieties.name as variety_name',
                    DB::raw('COALESCE(cost_center_varieties.development_state_id, 0) as state_id'),
                    'cost_center_varieties.fruit_id',
                    DB::raw('SUM(cost_center_varieties.surface) as total_surface')
                )
                ->groupBy('varieties.id', 'varieties.name', 'cost_center_varieties.development_state_id', 'cost_center_varieties.fruit_id')
                ->get()
                ->keyBy(fn($item) => $item->variety_name . '||' . $item->state_id . '||' . ($item->fruit_id ?? 0));

            return $results->map(function ($item) use ($surfaces) {
                $key = $item->variety_name . '||' . $item->state_id . '||' . ($item->fruit_id ?? 0);
                $surface = floatval($surfaces[$key]->total_surface ?? 0);
                $totalCost = floatval($item->total_cost);
                return [
                    'name'        => $item->variety_name,
                    'state'       => $item->state_name,
                    'state_id'    => intval($item->state_id),
                    'fruit_id'    => intval($item->fruit_id ?? 0),
                    'total_cost'  => $totalCost,
                    'surface'     => $surface,
                    'cost_per_ha' => $surface > 0 ? $totalCost / $surface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByVariety: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lista de estados de desarrollo presentes en cost_center_varieties
     */
    private function getVarietyDevStates($season_id, $team_id)
    {
        try {
            return DB::table('cost_center_varieties')
                ->join('development_states', 'cost_center_varieties.development_state_id', '=', 'development_states.id')
                ->where('cost_center_varieties.season_id', $season_id)
                ->where('cost_center_varieties.team_id', $team_id)
                ->select('development_states.id', 'development_states.name')
                ->distinct()
                ->orderBy('development_states.name')
                ->get()
                ->map(fn($item) => ['value' => $item->id, 'label' => $item->name])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getVarietyDevStates: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo por variedad desglosado por Level 2
     */
    private function getCostByVarietyLevel2($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $ccVarietySurfaceTotals = DB::table('cost_center_varieties')
                ->select('cost_center_id', DB::raw('SUM(surface) as total_variety_surface'))
                ->where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->groupBy('cost_center_id');

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->join('cost_center_varieties', 'cost_centers.id', '=', 'cost_center_varieties.cost_center_id')
                ->join('varieties', 'cost_center_varieties.variety_id', '=', 'varieties.id')
                ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
                ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
                ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
                ->leftJoinSub($ccVarietySurfaceTotals, 'ccv_totals', function ($join) {
                    $join->on('cost_centers.id', '=', 'ccv_totals.cost_center_id');
                })
                ->where('cost_center_varieties.season_id', $season_id)
                ->where('cost_center_varieties.team_id', $team_id)
                ->selectRaw("
                    varieties.name as variety_name,
                    COALESCE(cost_center_varieties.development_state_id, 0) as state_id,
                    COALESCE(cost_center_varieties.fruit_id, 0) as fruit_id,
                    COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                    COALESCE(level2s.name, 'Sin Clasificar') as level2_name,
                    COALESCE(level3s.name, 'Sin Clasificar') as level3_name,
                    COALESCE(SUM(
                        ({$amountExpr}) *
                        cost_center_varieties.surface / NULLIF(ccv_totals.total_variety_surface, 0)
                    ), 0) as total_cost
                ")
                ->groupBy('varieties.id', 'varieties.name', 'cost_center_varieties.development_state_id', 'cost_center_varieties.fruit_id', 'level1s.id', 'level1s.name', 'level2s.id', 'level2s.name', 'level3s.id', 'level3s.name')
                ->orderBy('varieties.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            return $results->map(function ($item) {
                return [
                    'variety'    => $item->variety_name,
                    'state_id'   => intval($item->state_id),
                    'fruit_id'   => intval($item->fruit_id ?? 0),
                    'category'   => $item->level1_name,
                    'name'       => $item->level2_name,
                    'level3'     => $item->level3_name,
                    'total_cost' => floatval($item->total_cost),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostByVarietyLevel2: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo total y costo/ha por centro de costo
     */
    private function getCostPerHaByCC($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->selectRaw("
                    cost_centers.id as cc_id,
                    cost_centers.name as cc_name,
                    cost_centers.surface as surface,
                    COALESCE(cost_centers.development_state_id, 0) as state_id,
                    COALESCE(fruits.id, 0) as fruit_id,
                    COALESCE(fruits.name, '-') as fruit_name,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('cost_centers.id', 'cost_centers.name', 'cost_centers.surface', 'cost_centers.development_state_id', 'fruits.id', 'fruits.name')
                ->havingRaw("COALESCE(SUM({$amountExpr}), 0) > 0")
                ->orderByRaw("CASE WHEN cost_centers.surface > 0 THEN COALESCE(SUM({$amountExpr}), 0) / cost_centers.surface ELSE 0 END DESC")
                ->get();

            return $results->map(function ($item) {
                $surface = floatval($item->surface);
                $totalCost = floatval($item->total_cost);
                return [
                    'cc_id'       => intval($item->cc_id),
                    'name'        => $item->cc_name,
                    'state_id'    => intval($item->state_id),
                    'fruit_id'    => intval($item->fruit_id ?? 0),
                    'fruit'       => $item->fruit_name,
                    'surface'     => $surface,
                    'total_cost'  => $totalCost,
                    'cost_per_ha' => $surface > 0 ? $totalCost / $surface : 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostPerHaByCC: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Costo por centro de costo desglosado por Level1 / Level2
     */
    private function getCostByCCLevel2($season_id, $team_id)
    {
        try {
            $amountExpr = $this->proratedAmountExpression();

            $results = $this->baseOutflowQuery($season_id, $team_id)
                ->leftJoin('level3s', 'outflows.level3_id', '=', 'level3s.id')
                ->leftJoin('level2s', 'level3s.level2_id', '=', 'level2s.id')
                ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
                ->leftJoin('fruits', 'cost_centers.fruit_id', '=', 'fruits.id')
                ->selectRaw("
                    cost_centers.id as cc_id,
                    cost_centers.name as cc_name,
                    COALESCE(cost_centers.development_state_id, 0) as state_id,
                    COALESCE(fruits.id, 0) as fruit_id,
                    COALESCE(level1s.name, 'Sin Clasificar') as level1_name,
                    COALESCE(level2s.name, 'Sin Clasificar') as level2_name,
                    COALESCE(level3s.name, 'Sin Clasificar') as level3_name,
                    COALESCE(SUM({$amountExpr}), 0) as total_cost
                ")
                ->groupBy('cost_centers.id', 'cost_centers.name', 'cost_centers.development_state_id', 'fruits.id', 'level1s.id', 'level1s.name', 'level2s.id', 'level2s.name', 'level3s.id', 'level3s.name')
                ->orderBy('cost_centers.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            return $results->map(function ($item) {
                return [
                    'cc_id'      => intval($item->cc_id),
                    'cc_name'    => $item->cc_name,
                    'state_id'   => intval($item->state_id),
                    'fruit_id'   => intval($item->fruit_id ?? 0),
                    'category'   => $item->level1_name,
                    'name'       => $item->level2_name,
                    'level3'     => $item->level3_name,
                    'total_cost' => floatval($item->total_cost),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('HectareDashboard getCostByCCLevel2: ' . $e->getMessage());
            return [];
        }
    }
}
