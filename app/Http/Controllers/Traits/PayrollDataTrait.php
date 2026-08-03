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
    public function getPayrollSummary(int $teamId, int $seasonId, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            // Prorrateo por superficie: solo la fracción proporcional al RS
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            $yieldRow = DB::table('daily_yield_cost_center as dycc')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->join('daily_yields as dy', function ($join) use ($teamId, $seasonId) {
                    $join->on('dycc.daily_yield_id', '=', 'dy.id')
                         ->where('dy.team_id', '=', $teamId)
                         ->where('dy.season_id', '=', $seasonId);
                })
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN COALESCE(dy.workdays, 0)
                            ELSE (cc.surface / surf_dy.total_surface) * COALESCE(dy.workdays, 0)
                        END
                    ), 0) as workdays
                ")
                ->first();

            $yieldsTotal = (float) ($yieldRow->total ?? 0);
            $workdays    = (float) ($yieldRow->workdays ?? 0);
        } else {
            $yieldsQuery = DB::table('daily_yields')
                ->where('team_id', $teamId)
                ->where('season_id', $seasonId);

            $yieldsTotal = (float) (clone $yieldsQuery)
                ->sum(DB::raw('COALESCE(amount, 0) + COALESCE(bonus_amount, 0) + COALESCE(target_price_bonus, 0)'));
            $workdays    = (float) (clone $yieldsQuery)->sum('workdays');
        }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        $bonusesTotal = 0.0;
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                $bonusRow = DB::table('monthly_bonus_cost_centers as mbcc')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->join('monthly_bonuses as mb', function ($join) use ($teamId, $seasonId, $contractIds) {
                        $join->on('mbcc.monthly_bonus_id', '=', 'mb.id')
                             ->where('mb.team_id', '=', $teamId)
                             ->where(function ($q) use ($seasonId) {
                                 $q->where('mb.season_id', '=', $seasonId)->orWhereNull('mb.season_id');
                             })
                             ->whereIn('mb.contract_id', $contractIds->toArray());
                    })
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total
                    ")
                    ->first();

                $bonusesTotal = (float) ($bonusRow->total ?? 0);
            } else {
                $bonusQuery = DB::table('monthly_bonuses')
                    ->where('team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('season_id', $seasonId)->orWhereNull('season_id');
                    })
                    ->whereIn('contract_id', $contractIds);

                $bonusesTotal = (float) $bonusQuery->sum('amount');
            }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        $overtimeTotal = 0.0;
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                $otRow = DB::table('overtime_hour_cost_centers as ohcc')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->join('overtime_hours as oh', function ($join) use ($teamId, $seasonId, $contractIds) {
                        $join->on('ohcc.overtime_hour_id', '=', 'oh.id')
                             ->where('oh.team_id', '=', $teamId)
                             ->where(function ($q) use ($seasonId) {
                                 $q->where('oh.season_id', '=', $seasonId)->orWhereNull('oh.season_id');
                             })
                             ->whereIn('oh.contract_id', $contractIds->toArray());
                    })
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total
                    ")
                    ->first();

                $overtimeTotal = (float) ($otRow->total ?? 0);
            } else {
                $otQuery = DB::table('overtime_hours')
                    ->where('team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('season_id', $seasonId)->orWhereNull('season_id');
                    })
                    ->whereIn('contract_id', $contractIds);

                $overtimeTotal = (float) $otQuery
                    ->sum(DB::raw('ROUND(hours * base_salary_snapshot * hourly_rate_factor_snapshot * overtime_multiplier_snapshot)'));
            }
        }

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
    public function getPayrollByDevelopmentState(int $teamId, int $seasonId, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS (daily_yields) ──────────────────────────────────────
        $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
            ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
            ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
            ->groupBy('dycc2.daily_yield_id');

        $yieldsQuery = DB::table('development_states')
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
            });

        if ($companyReasonId) {
            $yieldsQuery->whereIn('cc.company_reason_id', $companyReasonId);
        }

        $yieldsRows = $yieldsQuery->selectRaw("
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

            $bonusQuery = DB::table('development_states')
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
                });

            if ($companyReasonId) {
                $bonusQuery->whereIn('cc.company_reason_id', $companyReasonId);
            }

            $bonusRows = $bonusQuery->selectRaw("
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

            $otQuery = DB::table('development_states')
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
                });

            if ($companyReasonId) {
                $otQuery->whereIn('cc.company_reason_id', $companyReasonId);
            }

            $otRows = $otQuery->selectRaw("
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

    /**
     * Remuneraciones agrupadas por Level2.
     * Ruta: labor_type → level3 → level2 → level1
     * Cubre tarjas, bonos mensuales y horas extra.
     *
     * @return array ['level2Name' => ['total' => int, 'level1' => string]]
     */
    public function getPayrollByLevel2(int $teamId, int $seasonId, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->join('daily_yield_cost_center as dycc', 'dycc.daily_yield_id', '=', 'dy.id')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total")
                ->groupBy('l2.name', 'l1.name')
                ->get();
        } else {
            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    SUM(COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0)) as total")
                ->groupBy('l2.name', 'l1.name')
                ->get();
        }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        $bonusRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.monthly_bonus_id', '=', 'mb.id')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            } else {
                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        SUM(mb.amount) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        $otRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('overtime_hour_cost_centers as ohcc', 'ohcc.overtime_hour_id', '=', 'oh.id')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            } else {
                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── Merge ─────────────────────────────────────────────────────────
        $map = [];
        foreach ($yieldsRows->merge($bonusRows)->merge($otRows) as $row) {
            $key = $row->level2_name ?? 'Sin Clasificar';
            if (!isset($map[$key])) {
                $map[$key] = ['total' => 0.0, 'level1' => $row->level1_name ?? 'Sin Clasificar'];
            }
            $map[$key]['total'] += (float) $row->total;
        }

        return $map;
    }

    /**
     * Remuneraciones agrupadas por Level1/Level2/Level3 (labor_type.level3_id).
     * Igual ruta de clasificación que getPayrollByLevel2, pero sin colapsar Level3,
     * para poder fusionar el monto exacto en la fila correspondiente de la tabla
     * "Detalle por Categoría" (que sí distingue Level3).
     *
     * @return array [{level1: string, level2: string, level3: string, total: float}]
     */
    public function getPayrollByLevel3(int $teamId, int $seasonId, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->join('daily_yield_cost_center as dycc', 'dycc.daily_yield_id', '=', 'dy.id')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total")
                ->groupBy('l3.name', 'l2.name', 'l1.name')
                ->get();
        } else {
            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    SUM(COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0)) as total")
                ->groupBy('l3.name', 'l2.name', 'l1.name')
                ->get();
        }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        $bonusRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.monthly_bonus_id', '=', 'mb.id')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total")
                    ->groupBy('l3.name', 'l2.name', 'l1.name')
                    ->get();
            } else {
                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        SUM(mb.amount) as total")
                    ->groupBy('l3.name', 'l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        $otRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('overtime_hour_cost_centers as ohcc', 'ohcc.overtime_hour_id', '=', 'oh.id')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total")
                    ->groupBy('l3.name', 'l2.name', 'l1.name')
                    ->get();
            } else {
                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)), 0) as total")
                    ->groupBy('l3.name', 'l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── Merge ─────────────────────────────────────────────────────────
        $map = [];
        foreach ($yieldsRows->merge($bonusRows)->merge($otRows) as $row) {
            $level1 = $row->level1_name ?? 'Sin Clasificar';
            $level2 = $row->level2_name ?? 'Sin Clasificar';
            $level3 = $row->level3_name ?? 'Sin Clasificar';
            $key = $level1 . '||' . $level2 . '||' . $level3;
            if (!isset($map[$key])) {
                $map[$key] = ['level1' => $level1, 'level2' => $level2, 'level3' => $level3, 'total' => 0.0];
            }
            $map[$key]['total'] += (float) $row->total;
        }

        return array_values($map);
    }

    /**
     * Remuneraciones agrupadas por Level1/Level2/Level3 Y por mes de temporada, en una sola pasada.
     * Combina la granularidad de getPayrollByLevel3() con la indexación por mes de getPayrollMonthly().
     *
     * @param  array  $months  Array de 12 meses generado por generateMonthsArray() — cada elemento tiene 'id'
     * @return array [{level1: string, level2: string, level3: string, monthly: float[12]}]
     */
    public function getPayrollByLevel3Monthly(int $teamId, int $seasonId, array $months, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        $monthIndexMap = [];
        foreach ($months as $i => $m) {
            $monthIndexMap[(int) $m['id']] = $i;
        }

        $map = [];
        $addRow = function ($row, $monthNum) use (&$map, $monthIndexMap) {
            $idx = $monthIndexMap[(int) $monthNum] ?? null;
            if ($idx === null) return;
            $level1 = $row->level1_name ?? 'Sin Clasificar';
            $level2 = $row->level2_name ?? 'Sin Clasificar';
            $level3 = $row->level3_name ?? 'Sin Clasificar';
            $key = $level1 . '||' . $level2 . '||' . $level3;
            if (!isset($map[$key])) {
                $map[$key] = ['level1' => $level1, 'level2' => $level2, 'level3' => $level3, 'monthly' => array_fill(0, 12, 0.0)];
            }
            $map[$key]['monthly'][$idx] += (float) $row->total;
        };

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->join('daily_yield_cost_center as dycc', 'dycc.daily_yield_id', '=', 'dy.id')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("MONTH(dy.date) as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total")
                ->groupBy(DB::raw('MONTH(dy.date)'), 'l3.name', 'l2.name', 'l1.name')
                ->get();
        } else {
            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->selectRaw("MONTH(dy.date) as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    SUM(COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0)) as total")
                ->groupBy(DB::raw('MONTH(dy.date)'), 'l3.name', 'l2.name', 'l1.name')
                ->get();
        }
        foreach ($yieldsRows as $row) { $addRow($row, $row->month_num); }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.monthly_bonus_id', '=', 'mb.id')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("mb.month_id as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total")
                    ->groupBy('mb.month_id', 'l3.name', 'l2.name', 'l1.name')
                    ->get();
            } else {
                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->selectRaw("mb.month_id as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        SUM(mb.amount) as total")
                    ->groupBy('mb.month_id', 'l3.name', 'l2.name', 'l1.name')
                    ->get();
            }
            foreach ($bonusRows as $row) { $addRow($row, $row->month_num); }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('overtime_hour_cost_centers as ohcc', 'ohcc.overtime_hour_id', '=', 'oh.id')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("oh.month_id as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total")
                    ->groupBy('oh.month_id', 'l3.name', 'l2.name', 'l1.name')
                    ->get();
            } else {
                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->selectRaw("oh.month_id as month_num, COALESCE(l3.name, 'Sin Clasificar') as level3_name, COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)), 0) as total")
                    ->groupBy('oh.month_id', 'l3.name', 'l2.name', 'l1.name')
                    ->get();
            }
            foreach ($otRows as $row) { $addRow($row, $row->month_num); }
        }

        return array_values($map);
    }

    /**
     * Remuneraciones agrupadas por Level2 para UN mes específico (month_id 1-12).
     * Misma ruta de clasificación que getPayrollByLevel2, pero filtrado por mes:
     * tarjas por MONTH(date), bonos y HE por month_id.
     *
     * @return array ['level2Name' => ['total' => int, 'level1' => string]]
     */
    public function getPayrollByLevel2ForMonth(int $teamId, int $seasonId, int $monthId, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->join('daily_yield_cost_center as dycc', 'dycc.daily_yield_id', '=', 'dy.id')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereMonth('dy.date', $monthId)
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total")
                ->groupBy('l2.name', 'l1.name')
                ->get();
        } else {
            $yieldsRows = DB::table('daily_yields as dy')
                ->leftJoin('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
                ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereMonth('dy.date', $monthId)
                ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                    SUM(COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0)) as total")
                ->groupBy('l2.name', 'l1.name')
                ->get();
        }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        $bonusRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.monthly_bonus_id', '=', 'mb.id')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->where('mb.month_id', $monthId)
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            } else {
                $bonusRows = DB::table('monthly_bonuses as mb')
                    ->leftJoin('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->where('mb.month_id', $monthId)
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        SUM(mb.amount) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        $otRows = collect();
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->join('overtime_hour_cost_centers as ohcc', 'ohcc.overtime_hour_id', '=', 'oh.id')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->where('oh.month_id', $monthId)
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            } else {
                $otRows = DB::table('overtime_hours as oh')
                    ->leftJoin('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
                    ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
                    ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                    ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->where('oh.month_id', $monthId)
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->selectRaw("COALESCE(l2.name, 'Sin Clasificar') as level2_name, COALESCE(l1.name, 'Sin Clasificar') as level1_name,
                        COALESCE(SUM(ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)), 0) as total")
                    ->groupBy('l2.name', 'l1.name')
                    ->get();
            }
        }

        // ── Merge ─────────────────────────────────────────────────────────
        $map = [];
        foreach ($yieldsRows->merge($bonusRows)->merge($otRows) as $row) {
            $key = $row->level2_name ?? 'Sin Clasificar';
            if (!isset($map[$key])) {
                $map[$key] = ['total' => 0.0, 'level1' => $row->level1_name ?? 'Sin Clasificar'];
            }
            $map[$key]['total'] += (float) $row->total;
        }

        return $map;
    }

    /**
     * Remuneraciones agrupadas por mes (posición en el array de $months de la temporada).
     * Cubre tarjas (por MONTH(date)), bonos y HE (por month_id).
     *
     * @param  array  $months  Array de 12 meses generado por generateMonthsArray() — cada elemento tiene 'id'
     * @return int[]           Array de 12 enteros, indexado por posición del mes en la temporada
     */
    public function getPayrollMonthly(int $teamId, int $seasonId, array $months, array|null $companyReasonId = null): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // Mapa month_id (1-12) → índice en el array de la temporada
        $monthIndexMap = [];
        foreach ($months as $i => $m) {
            $monthIndexMap[(int) $m['id']] = $i;
        }

        $result = array_fill(0, 12, 0);

        // ── 1. TARJAS ─────────────────────────────────────────────────────
        if ($companyReasonId) {
            $yieldSurfaceTotals = DB::table('daily_yield_cost_center as dycc2')
                ->join('cost_centers as cc2', 'dycc2.cost_center_id', '=', 'cc2.id')
                ->select('dycc2.daily_yield_id', DB::raw('SUM(cc2.surface) as total_surface'))
                ->groupBy('dycc2.daily_yield_id');

            DB::table('daily_yields as dy')
                ->join('daily_yield_cost_center as dycc', 'dycc.daily_yield_id', '=', 'dy.id')
                ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
                ->leftJoinSub($yieldSurfaceTotals, 'surf_dy', 'dycc.daily_yield_id', '=', 'surf_dy.daily_yield_id')
                ->where('dy.team_id', $teamId)
                ->where('dy.season_id', $seasonId)
                ->whereIn('cc.company_reason_id', $companyReasonId)
                ->selectRaw("
                    MONTH(dy.date) as month_num,
                    COALESCE(SUM(
                        CASE WHEN cc.surface = 0 OR COALESCE(surf_dy.total_surface, 0) = 0
                            THEN (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                            ELSE (cc.surface / surf_dy.total_surface) * (COALESCE(dy.amount,0) + COALESCE(dy.bonus_amount,0) + COALESCE(dy.target_price_bonus,0))
                        END
                    ), 0) as total
                ")
                ->groupBy(DB::raw('MONTH(dy.date)'))
                ->get()
                ->each(function ($row) use (&$result, $monthIndexMap) {
                    $idx = $monthIndexMap[(int) $row->month_num] ?? null;
                    if ($idx !== null) $result[$idx] += (float) $row->total;
                });
        } else {
            DB::table('daily_yields')
                ->where('team_id', $teamId)
                ->where('season_id', $seasonId)
                ->selectRaw('MONTH(date) as month_num, SUM(COALESCE(amount,0) + COALESCE(bonus_amount,0) + COALESCE(target_price_bonus,0)) as total')
                ->groupBy('month_num')
                ->get()
                ->each(function ($row) use (&$result, $monthIndexMap) {
                    $idx = $monthIndexMap[(int) $row->month_num] ?? null;
                    if ($idx !== null) $result[$idx] += (float) $row->total;
                });
        }

        // ── 2. BONOS MENSUALES ────────────────────────────────────────────
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $bonusSurfaceTotals = DB::table('monthly_bonus_cost_centers as mbcc2')
                    ->join('cost_centers as cc2', 'mbcc2.cost_center_id', '=', 'cc2.id')
                    ->select('mbcc2.monthly_bonus_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('mbcc2.monthly_bonus_id');

                DB::table('monthly_bonuses as mb')
                    ->join('monthly_bonus_cost_centers as mbcc', 'mbcc.monthly_bonus_id', '=', 'mb.id')
                    ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($bonusSurfaceTotals, 'surf_mb', 'mbcc.monthly_bonus_id', '=', 'surf_mb.monthly_bonus_id')
                    ->where('mb.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('mb.season_id', $seasonId)->orWhereNull('mb.season_id');
                    })
                    ->whereIn('mb.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("
                        mb.month_id,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_mb.total_surface, 0) = 0
                                THEN COALESCE(mb.amount, 0)
                                ELSE (cc.surface / surf_mb.total_surface) * COALESCE(mb.amount, 0)
                            END
                        ), 0) as total
                    ")
                    ->groupBy('mb.month_id')
                    ->get()
                    ->each(function ($row) use (&$result, $monthIndexMap) {
                        $idx = $monthIndexMap[(int) $row->month_id] ?? null;
                        if ($idx !== null) $result[$idx] += (float) $row->total;
                    });
            } else {
                DB::table('monthly_bonuses')
                    ->where('team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('season_id', $seasonId)->orWhereNull('season_id');
                    })
                    ->whereIn('contract_id', $contractIds)
                    ->selectRaw('month_id, SUM(amount) as total')
                    ->groupBy('month_id')
                    ->get()
                    ->each(function ($row) use (&$result, $monthIndexMap) {
                        $idx = $monthIndexMap[(int) $row->month_id] ?? null;
                        if ($idx !== null) $result[$idx] += (float) $row->total;
                    });
            }
        }

        // ── 3. HORAS EXTRA ────────────────────────────────────────────────
        if ($contractIds->isNotEmpty()) {
            if ($companyReasonId) {
                $otSurfaceTotals = DB::table('overtime_hour_cost_centers as ohcc2')
                    ->join('cost_centers as cc2', 'ohcc2.cost_center_id', '=', 'cc2.id')
                    ->select('ohcc2.overtime_hour_id', DB::raw('SUM(cc2.surface) as total_surface'))
                    ->groupBy('ohcc2.overtime_hour_id');

                DB::table('overtime_hours as oh')
                    ->join('overtime_hour_cost_centers as ohcc', 'ohcc.overtime_hour_id', '=', 'oh.id')
                    ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
                    ->leftJoinSub($otSurfaceTotals, 'surf_oh', 'ohcc.overtime_hour_id', '=', 'surf_oh.overtime_hour_id')
                    ->where('oh.team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('oh.season_id', $seasonId)->orWhereNull('oh.season_id');
                    })
                    ->whereIn('oh.contract_id', $contractIds->toArray())
                    ->whereIn('cc.company_reason_id', $companyReasonId)
                    ->selectRaw("
                        oh.month_id,
                        COALESCE(SUM(
                            CASE WHEN cc.surface = 0 OR COALESCE(surf_oh.total_surface, 0) = 0
                                THEN ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                                ELSE (cc.surface / surf_oh.total_surface) * ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot)
                            END
                        ), 0) as total
                    ")
                    ->groupBy('oh.month_id')
                    ->get()
                    ->each(function ($row) use (&$result, $monthIndexMap) {
                        $idx = $monthIndexMap[(int) $row->month_id] ?? null;
                        if ($idx !== null) $result[$idx] += (float) $row->total;
                    });
            } else {
                DB::table('overtime_hours')
                    ->where('team_id', $teamId)
                    ->where(function ($q) use ($seasonId) {
                        $q->where('season_id', $seasonId)->orWhereNull('season_id');
                    })
                    ->whereIn('contract_id', $contractIds)
                    ->selectRaw('month_id, SUM(ROUND(hours * base_salary_snapshot * hourly_rate_factor_snapshot * overtime_multiplier_snapshot)) as total')
                    ->groupBy('month_id')
                    ->get()
                    ->each(function ($row) use (&$result, $monthIndexMap) {
                        $idx = $monthIndexMap[(int) $row->month_id] ?? null;
                        if ($idx !== null) $result[$idx] += (float) $row->total;
                    });
            }
        }

        return array_map('intval', $result);
    }
}
