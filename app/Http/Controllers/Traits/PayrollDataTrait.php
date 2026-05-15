<?php

namespace App\Http\Controllers\Traits;

use App\Models\Contract;
use Illuminate\Support\Facades\DB;

trait PayrollDataTrait
{
    /**
     * Resumen total de remuneraciones para una temporada/equipo.
     * Incluye tarjas (daily_yields), bonos mensuales y horas extra.
     *
     * @return array{total: int, workdays: float}
     */
    public function getPayrollSummary(int $teamId, int $seasonId): array
    {
        // IDs de contratos del equipo (bonos y HE no tienen season_id directo)
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // Tarjas: filtradas por team + season
        $yieldsTotal = (float) DB::table('daily_yields')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->sum(DB::raw('COALESCE(amount, 0) + COALESCE(bonus_amount, 0) + COALESCE(target_price_bonus, 0)'));

        $workdays = (float) DB::table('daily_yields')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->sum('workdays');

        // Bonos mensuales: filtrados por team + season + contratos del equipo
        $bonusesTotal = $contractIds->isEmpty() ? 0.0 : (float) DB::table('monthly_bonuses')
            ->where('team_id', $teamId)
            ->where(function ($q) use ($seasonId) {
                $q->where('season_id', $seasonId)->orWhereNull('season_id');
            })
            ->whereIn('contract_id', $contractIds)
            ->sum('amount');

        // Horas extra: filtradas por team + season + contratos del equipo
        $overtimeTotal = $contractIds->isEmpty() ? 0.0 : (float) DB::table('overtime_hours')
            ->where('team_id', $teamId)
            ->where(function ($q) use ($seasonId) {
                $q->where('season_id', $seasonId)->orWhereNull('season_id');
            })
            ->whereIn('contract_id', $contractIds)
            ->sum(DB::raw('ROUND(hours * base_salary_snapshot * hourly_rate_factor_snapshot * overtime_multiplier_snapshot)'));

        return [
            'total'    => (int) round($yieldsTotal + $bonusesTotal + $overtimeTotal),
            'workdays' => round($workdays, 2),
        ];
    }

    /**
     * Remuneraciones prorateadas por estado de desarrollo.
     * Usa la misma lógica de prorrateo por superficie que getTotalsByDevelopmentState.
     *
     * @return array [{id, name, total}]
     */
    public function getPayrollByDevelopmentState(int $teamId, int $seasonId): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS (daily_yields) ──────────────────────────────────────
        $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
            ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
            ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
            ->groupBy('dycc2.daily_yield_id');

        $yieldsRows = DB::table('development_states')
            ->join('cost_centers as cc', function ($join) use ($seasonId) {
                $join->on('cc.development_state_id', '=', 'development_states.id')
                     ->where('cc.season_id', '=', $seasonId);
            })
            ->join('daily_yield_cost_center as dycc', 'dycc.cost_center_id', '=', 'cc.id')
            ->join('daily_yields as dy', function ($join) use ($teamId, $seasonId) {
                $join->on('dycc.daily_yield_id', '=', 'dy.id')
                     ->where('dy.team_id', '=', $teamId)
                     ->where('dy.season_id', '=', $seasonId);
            })
            ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', function ($join) {
                $join->on('dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id');
            })
            ->selectRaw("
                development_states.id,
                development_states.name as state_name,
                COALESCE(SUM(
                    CASE
                        WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0 THEN
                            (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        ELSE
                            (cc.surface / surf_dy.total_surface) *
                            (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                    END
                ), 0) as total
            ")
            ->groupBy('development_states.id', 'development_states.name')
            ->get()
            ->keyBy('id');

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        $bonusRows = collect();
        if ($contractIds->isNotEmpty()) {
            $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('mbcc2.monthly_bonus_id');

            $bonusRows = DB::table('development_states')
                ->join('cost_centers as cc', function ($join) use ($seasonId) {
                    $join->on('cc.development_state_id', '=', 'development_states.id')
                         ->where('cc.season_id', '=', $seasonId);
                })
                ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.cost_center_id', '=', 'cc.id')
                ->join('monthly_bonuses as mb', function ($join) use ($teamId, $seasonId, $contractIds) {
                    $join->on('mbcc.monthly_bonus_id', '=', 'mb.id')
                         ->where('mb.team_id', '=', $teamId)
                         ->where(function ($q) use ($seasonId) {
                             $q->where('mb.season_id', '=', $seasonId)->orWhereNull('mb.season_id');
                         })
                         ->whereIn('mb.contract_id', $contractIds->toArray());
                })
                ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', function ($join) {
                    $join->on('mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id');
                })
                ->selectRaw("
                    development_states.id,
                    development_states.name as state_name,
                    COALESCE(SUM(
                        CASE
                            WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0 THEN
                                COALESCE(mb.amount, 0)
                            ELSE
                                (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                        END
                    ), 0) as total
                ")
                ->groupBy('development_states.id', 'development_states.name')
                ->get()
                ->keyBy('id');
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        $otRows = collect();
        if ($contractIds->isNotEmpty()) {
            $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('ohcc2.overtime_hour_id');

            $otRows = DB::table('development_states')
                ->join('cost_centers as cc', function ($join) use ($seasonId) {
                    $join->on('cc.development_state_id', '=', 'development_states.id')
                         ->where('cc.season_id', '=', $seasonId);
                })
                ->join('overtime_hour_cost_centers as ohcc', 'ohcc.cost_center_id', '=', 'cc.id')
                ->join('overtime_hours as oh', function ($join) use ($teamId, $seasonId, $contractIds) {
                    $join->on('ohcc.overtime_hour_id', '=', 'oh.id')
                         ->where('oh.team_id', '=', $teamId)
                         ->where(function ($q) use ($seasonId) {
                             $q->where('oh.season_id', '=', $seasonId)->orWhereNull('oh.season_id');
                         })
                         ->whereIn('oh.contract_id', $contractIds->toArray());
                })
                ->leftJoinSub($otSurfaceTotals, 'surf_oh', function ($join) {
                    $join->on('ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id');
                })
                ->selectRaw("
                    development_states.id,
                    development_states.name as state_name,
                    COALESCE(SUM(
                        CASE
                            WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0 THEN
                                ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            ELSE
                                (cc.surface / surf_oh.total_surface) *
                                ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                        END
                    ), 0) as total
                ")
                ->groupBy('development_states.id', 'development_states.name')
                ->get()
                ->keyBy('id');
        }

        // ── Merge de los tres orígenes ────────────────────────────────────
        $allIds = array_unique(array_merge(
            $yieldsRows->keys()->toArray(),
            $bonusRows->keys()->toArray(),
            $otRows->keys()->toArray(),
        ));

        $result = [];
        foreach ($allIds as $id) {
            $y = $yieldsRows->get($id);
            $b = $bonusRows->get($id);
            $o = $otRows->get($id);
            $result[] = [
                'id'    => $id,
                'name'  => $y?->state_name ?? $b?->state_name ?? $o?->state_name ?? 'Sin Estado',
                'total' => (int) round(($y?->total ?? 0) + ($b?->total ?? 0) + ($o?->total ?? 0)),
            ];
        }

        usort($result, fn ($a, $b) => $b['total'] - $a['total']);

        return $result;
    }
}
