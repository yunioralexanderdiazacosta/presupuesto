<?php

namespace App\Http\Controllers;

use App\Models\ProductionSummary;
use App\Models\CostCenterVariety;
use App\Models\Variety;
use App\Models\Fruit;
use App\Models\DevelopmentState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProfitLossController extends Controller
{
    public function index()
    {
        $season_id = session('season_id');
        $user = Auth::user();
        $team_id = $user->team_id;

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        $adminUser = \App\Models\User::where('team_id', $team_id)->role('Admin')->first();
        $dollarPrice = $adminUser?->dollar_price ?? 970;

        try {
            $fruits = $this->getFruits($season_id, $team_id);
            $devStates = $this->getDevelopmentStates($season_id, $team_id);
            $varieties = $this->getVarieties($season_id, $team_id);
            $income = $this->getIncomeData($season_id, $team_id);
            $costs = $this->getCostsByVariety($season_id, $team_id);
            $surfaces = $this->getSurfaces($season_id, $team_id);
        } catch (\Exception $e) {
            Log::error('ProfitLoss: ' . $e->getMessage());
            $fruits = [];
            $devStates = [];
            $varieties = [];
            $income = [];
            $costs = [];
            $surfaces = [];
        }

        return Inertia::render('ProfitLoss/Index', [
            'dollarPrice' => $dollarPrice,
            'isAdmin'     => $user->hasRole('Admin'),
            'fruits'      => $fruits,
            'developmentStates' => $devStates,
            'varieties'   => $varieties,
            'income'      => $income,
            'costs'       => $costs,
            'surfaces'    => $surfaces,
        ]);
    }

    private function getFruits($season_id, $team_id)
    {
        $fruitIds = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->distinct()
            ->pluck('fruit_id');

        return Fruit::whereIn('id', $fruitIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($f) => ['value' => $f->id, 'label' => $f->name])
            ->toArray();
    }

    private function getDevelopmentStates($season_id, $team_id)
    {
        // IDs desde CostCenterVariety
        $idsFromCCV = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->whereNotNull('development_state_id')
            ->distinct()
            ->pluck('development_state_id');

        // IDs desde CostCenters vinculados a outflows del equipo/temporada
        $idsFromCC = DB::table('cost_centers as cc')
            ->join('outflow_cost_center as occ', 'cc.id', '=', 'occ.cost_center_id')
            ->join('outflows as o', 'occ.outflow_id', '=', 'o.id')
            ->where('o.season_id', $season_id)
            ->where('o.team_id', $team_id)
            ->whereNotNull('cc.development_state_id')
            ->distinct()
            ->pluck('cc.development_state_id');

        $allIds = $idsFromCCV->merge($idsFromCC)->unique();

        return DevelopmentState::whereIn('id', $allIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }

    private function getVarieties($season_id, $team_id)
    {
        $idsFromCCV = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->pluck('variety_id');

        $idsFromPS = ProductionSummary::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->pluck('variety_id');

        $allIds = $idsFromCCV->merge($idsFromPS)->unique();

        return Variety::whereIn('id', $allIds)
            ->with('fruit:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'fruit_id'])
            ->map(fn($v) => [
                'id'         => $v->id,
                'name'       => $v->name,
                'fruit_id'   => $v->fruit_id,
                'fruit_name' => $v->fruit?->name ?? '',
            ])
            ->toArray();
    }

    private function getIncomeData($season_id, $team_id)
    {
        return ProductionSummary::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->get()
            ->map(function ($ps) {
                $exported = (float) ($ps->kg_exported ?? 0);
                $harvested = (float) ($ps->kg_harvested ?? 0);
                $netKilo = (float) ($ps->net_kilo ?? 0);
                $commercialCostPerKg = (float) ($ps->commercial_cost_per_kg ?? 0);
                $commercialKg = max(0, $harvested - $exported);
                $exportReturn = $exported * $netKilo;
                $commercialDiscount = $commercialKg > 0 && $commercialCostPerKg > 0
                    ? $commercialKg * $commercialCostPerKg : 0;

                return [
                    'variety_id'   => $ps->variety_id,
                    'kg_harvested' => round($harvested),
                    'kg_exported'  => round($exported),
                    'commercial_kg' => round($commercialKg),
                    'net_kilo'     => $netKilo,
                    'commercial_cost_per_kg' => $commercialCostPerKg,
                    'income_usd'   => round($exportReturn, 2),
                    'commercial_cost_usd' => round($commercialDiscount, 2),
                ];
            })
            ->keyBy('variety_id')
            ->toArray();
    }

    /**
     * Costos por variedad prorrateados:
     * 1. Outflow → CCs (por superficie del CC / total superficie CCs del outflow)
     * 2. CC → CCVs (por superficie CCV / total superficie CCVs del CC)
     * 3. CCs sin CCVs (ej: Admin) → prorrateados a todas las variedades por superficie
     */
    private function getCostsByVariety($season_id, $team_id)
    {
        // Subquery: superficie total de todos los CCs por outflow (prorrateo 1)
        $surfaceTotals = DB::table('outflow_cost_center as occ')
            ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
            ->select('occ.outflow_id', DB::raw('SUM(cc.surface) as total_surface'))
            ->groupBy('occ.outflow_id');

        // Subquery: superficie total de CCVs por CC (prorrateo 2)
        $ccvTotals = DB::table('cost_center_varieties')
            ->where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->select('cost_center_id', DB::raw('SUM(surface) as total_ccv_surface'))
            ->groupBy('cost_center_id');

        // ── Query 1: CCs CON CCVs (producción, año X, etc.) ──
        $costsWithCCV = DB::table('outflows as o')
            ->join('outflow_cost_center as occ', 'o.id', '=', 'occ.outflow_id')
            ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
            ->join('cost_center_varieties as ccv', function ($join) use ($season_id, $team_id) {
                $join->on('cc.id', '=', 'ccv.cost_center_id')
                    ->where('ccv.season_id', '=', $season_id)
                    ->where('ccv.team_id', '=', $team_id);
            })
            ->leftJoin('operations as op', 'o.operation_id', '=', 'op.id')
            ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
            ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
            ->leftJoinSub($surfaceTotals, 'st', function ($join) {
                $join->on('o.id', '=', 'st.outflow_id');
            })
            ->leftJoinSub($ccvTotals, 'ccvt', function ($join) {
                $join->on('cc.id', '=', 'ccvt.cost_center_id');
            })
            ->where('o.season_id', $season_id)
            ->where('o.team_id', $team_id)
            ->whereNull('o.fuel_outflow_id')
            ->selectRaw("
                ccv.variety_id,
                ccv.development_state_id,
                COALESCE(SUM(
                    CASE
                        WHEN COALESCE(st.total_surface, 0) > 0 AND COALESCE(ccvt.total_ccv_surface, 0) > 0 THEN
                            (cc.surface / st.total_surface) *
                            (ccv.surface / ccvt.total_ccv_surface) *
                            o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                        WHEN COALESCE(st.total_surface, 0) = 0 AND COALESCE(ccvt.total_ccv_surface, 0) > 0 THEN
                            (ccv.surface / ccvt.total_ccv_surface) *
                            o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                        ELSE 0
                    END
                ), 0) as cost_total,
                COALESCE(SUM(
                    CASE WHEN LOWER(COALESCE(op.name, '')) NOT LIKE '%inversion%' THEN
                        CASE
                            WHEN COALESCE(st.total_surface, 0) > 0 AND COALESCE(ccvt.total_ccv_surface, 0) > 0 THEN
                                (cc.surface / st.total_surface) *
                                (ccv.surface / ccvt.total_ccv_surface) *
                                o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                            WHEN COALESCE(st.total_surface, 0) = 0 AND COALESCE(ccvt.total_ccv_surface, 0) > 0 THEN
                                (ccv.surface / ccvt.total_ccv_surface) *
                                o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                            ELSE 0
                        END
                    ELSE 0 END
                ), 0) as cost_no_inv
            ")
            ->groupBy('ccv.variety_id', 'ccv.development_state_id')
            ->get();

        // ── Query 2: CCs SIN CCVs (admin, etc.) → prorratear a variedades por superficie ──
        // IDs de CCs que SÍ tienen CCVs
        $ccIdsWithCCV = DB::table('cost_center_varieties')
            ->where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->distinct()
            ->pluck('cost_center_id');

        $costsWithoutCCV = DB::table('outflows as o')
            ->join('outflow_cost_center as occ', 'o.id', '=', 'occ.outflow_id')
            ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
            ->leftJoin('operations as op', 'o.operation_id', '=', 'op.id')
            ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
            ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
            ->leftJoinSub($surfaceTotals, 'st', function ($join) {
                $join->on('o.id', '=', 'st.outflow_id');
            })
            ->where('o.season_id', $season_id)
            ->where('o.team_id', $team_id)
            ->whereNull('o.fuel_outflow_id')
            ->whereNotIn('cc.id', $ccIdsWithCCV)
            ->whereNotNull('cc.development_state_id')
            ->selectRaw("
                cc.development_state_id,
                COALESCE(SUM(
                    CASE
                        WHEN COALESCE(st.total_surface, 0) > 0 THEN
                            (cc.surface / st.total_surface) *
                            o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                        ELSE
                            o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                    END
                ), 0) as cost_total,
                COALESCE(SUM(
                    CASE WHEN LOWER(COALESCE(op.name, '')) NOT LIKE '%inversion%' THEN
                        CASE
                            WHEN COALESCE(st.total_surface, 0) > 0 THEN
                                (cc.surface / st.total_surface) *
                                o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                            ELSE
                                o.quantity * COALESCE(ip.unit_price, cdni.unit_price, 0)
                        END
                    ELSE 0 END
                ), 0) as cost_no_inv
            ")
            ->groupBy('cc.development_state_id')
            ->get();

        // Obtener superficie total por variedad Y dev_state para prorrateo correcto
        $varietySurfacesByDevState = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->select('variety_id', 'development_state_id', DB::raw('SUM(surface) as total_surface'))
            ->groupBy('variety_id', 'development_state_id')
            ->get();

        // Combinar resultado 1
        $results = $costsWithCCV->map(fn($row) => [
            'variety_id'           => (int) $row->variety_id,
            'development_state_id' => (int) $row->development_state_id,
            'cost_total'           => round((float) $row->cost_total, 2),
            'cost_no_inv'          => round((float) $row->cost_no_inv, 2),
        ])->toArray();

        // Distribuir costos sin CCV: prorratear por superficie TOTAL de todas las variedades
        // pero asignarle el development_state_id del CC (ej: admin)
        $allVarietySurfaces = $varietySurfacesByDevState
            ->groupBy('variety_id')
            ->map(fn($group) => $group->sum('total_surface'));

        $grandTotalSurface = $allVarietySurfaces->sum();

        if ($grandTotalSurface > 0) {
            foreach ($costsWithoutCCV as $row) {
                foreach ($allVarietySurfaces as $varietyId => $surface) {
                    $ratio = $surface / $grandTotalSurface;
                    $results[] = [
                        'variety_id'           => (int) $varietyId,
                        'development_state_id' => (int) $row->development_state_id,
                        'cost_total'           => round((float) $row->cost_total * $ratio, 2),
                        'cost_no_inv'          => round((float) $row->cost_no_inv * $ratio, 2),
                    ];
                }
            }
        }

        return $results;
    }

    private function getSurfaces($season_id, $team_id)
    {
        return CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $team_id)
            ->select('variety_id', 'fruit_id', 'development_state_id', DB::raw('SUM(surface) as surface'))
            ->groupBy('variety_id', 'fruit_id', 'development_state_id')
            ->get()
            ->map(fn($row) => [
                'variety_id'           => $row->variety_id,
                'fruit_id'             => $row->fruit_id,
                'development_state_id' => $row->development_state_id,
                'surface'              => (float) $row->surface,
            ])
            ->toArray();
    }
}
