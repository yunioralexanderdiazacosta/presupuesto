<?php

namespace App\Http\Controllers\PayrollReports;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Season;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayrollDashboardController extends Controller
{
    public function __invoke()
    {
        $user     = Auth::user();
        $teamId   = $user->team_id;
        $seasonId = session('season_id');

        if (!$seasonId) {
            return redirect()->route('select.budget');
        }

        $season        = Season::with('month')->find($seasonId);
        $startMonthId  = $season->month_id ?? 1;
        $months        = $this->generateMonths($startMonthId);

        // IDs de contratos del equipo (para bonos y HE que no tienen season_id)
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        // Superficies de todos los CC (necesarias para el prorrateo)
        $surfaces = DB::table('cost_centers')
            ->where('season_id', $seasonId)
            ->pluck('surface', 'id');

        // Parcelas
        $parcels = DB::table('parcels')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->pluck('name', 'id');

        // parcel_id por CC
        $ccParcelMap = DB::table('cost_centers')
            ->where('season_id', $seasonId)
            ->pluck('parcel_id', 'id');

        // Sucursales
        $branches = DB::table('branches')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $branchNames = $branches->pluck('name', 'id');

        // branch_id por parcela (para filtrado frontend)
        $parcelBranchMap = DB::table('cost_centers')
            ->where('season_id', $seasonId)
            ->whereNotNull('parcel_id')
            ->whereNotNull('branch_id')
            ->select('parcel_id', 'branch_id')
            ->get()
            ->unique('parcel_id')
            ->pluck('branch_id', 'parcel_id');

        // ============================================================
        // 1. DAILY YIELDS — carga única con columnas mínimas
        // ============================================================
        $yields = DB::table('daily_yields as dy')
            ->join('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->leftJoin('labor_rates as lr', 'dy.labor_rate_id', '=', 'lr.id')
            ->where('dy.team_id', $teamId)
            ->where('dy.season_id', $seasonId)
            ->select(
                'dy.id',
                'dy.employee_id',
                DB::raw('MONTH(dy.date) as month_id'),
                'dy.payment_type',
                'dy.amount',
                'dy.bonus_amount',
                'dy.target_price_bonus',
                'dy.workdays',
                'dy.quantity',
                'dy.rate',
                'dy.labor_rate_id',
                'lr.name as labor_rate_name',
                'l3.id as level3_id',
                'l3.name as level3_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'lt.id as labor_type_id',
                'lt.name as labor_type_name',
                'l1.name as level1_name'
            )
            ->get();

        // Pivot CC de yields
        $yieldIds = $yields->pluck('id');
        $yieldCCs = $yieldIds->isEmpty() ? collect() : DB::table('daily_yield_cost_center as dycc')
            ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
            ->whereIn('dycc.daily_yield_id', $yieldIds)
            ->select('dycc.daily_yield_id', 'dycc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id', 'cc.company_reason_id')
            ->get()
            ->groupBy('daily_yield_id');

        // ============================================================
        // 2. MONTHLY BONUSES — carga única
        // ============================================================
        $bonuses = DB::table('monthly_bonuses as mb')
            ->join('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('mb.team_id', $teamId)
            ->whereIn('mb.contract_id', $contractIds)
            ->select(
                'mb.id',
                'mb.contract_id',
                'mb.month_id',
                'mb.amount',
                'l3.id as level3_id',
                'l3.name as level3_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'lt.id as labor_type_id',
                'lt.name as labor_type_name',
                'l1.name as level1_name'
            )
            ->get();

        $bonusIds = $bonuses->pluck('id');
        $bonusCCs = $bonusIds->isEmpty() ? collect() : DB::table('monthly_bonus_cost_centers as mbcc')
            ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
            ->whereIn('mbcc.monthly_bonus_id', $bonusIds)
            ->select('mbcc.monthly_bonus_id', 'mbcc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id', 'cc.company_reason_id')
            ->get()
            ->groupBy('monthly_bonus_id');

        // ============================================================
        // 3. OVERTIME HOURS — carga única
        // ============================================================
        $overtimes = DB::table('overtime_hours as oh')
            ->join('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('oh.team_id', $teamId)
            ->whereIn('oh.contract_id', $contractIds)
            ->select(
                'oh.id',
                'oh.contract_id',
                'oh.month_id',
                DB::raw('ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot) as amount'),
                'l3.id as level3_id',
                'l3.name as level3_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'lt.id as labor_type_id',
                'lt.name as labor_type_name',
                'l1.name as level1_name'
            )
            ->get();

        $overtimeIds = $overtimes->pluck('id');
        $overtimeCCs = $overtimeIds->isEmpty() ? collect() : DB::table('overtime_hour_cost_centers as ohcc')
            ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
            ->whereIn('ohcc.overtime_hour_id', $overtimeIds)
            ->select('ohcc.overtime_hour_id', 'ohcc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id', 'cc.company_reason_id')
            ->get()
            ->groupBy('overtime_hour_id');

        // ============================================================
        // Procesar todas las métricas
        // ============================================================
        // Mapa de razones sociales del equipo
        $companyReasonNames = DB::table('company_reasons')
            ->where('team_id', $teamId)
            ->pluck('name', 'id');

        // Mapa employee_id → company_reason_id (para daily_yields, sin contract_id)
        // Sin filtro is_active: incluir contratos finalizados para no perder rendimientos
        // orderBy id asc → pluck sobreescribe con el contrato más reciente (id mayor)
        $employeeCRMap = DB::table('contracts')
            ->where('team_id', $teamId)
            ->whereNotNull('company_reason_id')
            ->orderBy('id', 'asc')
            ->pluck('company_reason_id', 'employee_id');

        // Mapa contract_id → company_reason_id (para bonuses y overtimes)
        $contractCRMap = DB::table('contracts')
            ->where('team_id', $teamId)
            ->whereNotNull('company_reason_id')
            ->pluck('company_reason_id', 'id');

        $byMonth             = $this->buildByMonth($yields, $bonuses, $overtimes, $months);
        $byLevel             = $this->buildByLevel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $months);
        $byParcel            = $this->buildByParcel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $parcels, $months);
        $byBranch            = $this->buildByBranch($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $branchNames, $months);
        $byTrato             = $this->buildByTrato($yields, $yieldCCs, $months);
        $byCompanyReason     = $this->buildByCompanyReason($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $companyReasonNames, $months);
        $byRSDetail          = $this->buildByRSParcelDetail($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $employeeCRMap, $contractCRMap, $companyReasonNames, $parcels, $months);

        // Detalles de CC para el reporte de labores por CC
        $ccDetails = DB::table('cost_centers as cc')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('parcels as pa', 'cc.parcel_id', '=', 'pa.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->where('cc.season_id', $seasonId)
            ->select(
                'cc.id',
                'cc.name',
                'cc.branch_id',
                'cc.parcel_id',
                'cc.company_reason_id',
                DB::raw("COALESCE(br.name, 'Sin Sucursal') as branch_name"),
                DB::raw("COALESCE(pa.name, 'Sin Parcela') as parcel_name"),
                DB::raw("COALESCE(cr.name, 'Sin RS') as company_reason_name")
            )
            ->get()
            ->keyBy('id');

        $byCostCenter = $this->buildByCostCenter($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $months, $ccDetails);
        $seasonTotals = $this->buildSeasonTotals($byMonth);
        $chartData    = $this->buildChartData($byMonth, $months);

        return Inertia::render('PayrollDashboard', [
            'byMonth'          => $byMonth,
            'byLevel'          => $byLevel,
            'byParcel'         => $byParcel,
            'byBranch'         => $byBranch,
            'byTrato'          => $byTrato,
            'byCompanyReason'  => $byCompanyReason,
            'byRSDetail'       => $byRSDetail,
            'byCostCenter'     => $byCostCenter,
            'seasonTotals'     => $seasonTotals,
            'chartData'        => $chartData,
            'months'           => $months,
            'seasonStartMonth' => $startMonthId,
            'branches'         => $branches,
            'parcelBranchMap'  => $parcelBranchMap,
        ]);
    }

    // ================================================================
    // HELPERS DE CONSTRUCCIÓN
    // ================================================================

    /**
     * Genera array de 12 meses desde el mes de inicio de la temporada.
     */
    private function generateMonths(int $startMonthId): array
    {
        $names = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $id = (($startMonthId + $i - 1) % 12) + 1;
            $months[] = [
                'id'         => $id,
                'name'       => $names[$id],
                'short_name' => substr($names[$id], 0, 3),
            ];
        }
        return $months;
    }

    /**
     * Totales por mes: amount total + workdays.
     * Retorna array indexado por month_id.
     */
    private function buildByMonth($yields, $bonuses, $overtimes, array $months): array
    {
        $result = [];
        foreach ($months as $m) {
            $result[$m['id']] = ['amount' => 0, 'workdays' => 0.0];
        }

        foreach ($yields as $y) {
            $mid = $y->month_id;
            if (!isset($result[$mid])) continue;
            $total = ($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0);
            $result[$mid]['amount']   += $total;
            $result[$mid]['workdays'] += (float) ($y->workdays ?? 0);
        }

        foreach ($bonuses as $b) {
            if (!isset($result[$b->month_id])) continue;
            $result[$b->month_id]['amount'] += (int) ($b->amount ?? 0);
        }

        foreach ($overtimes as $o) {
            if (!isset($result[$o->month_id])) continue;
            $result[$o->month_id]['amount'] += (int) ($o->amount ?? 0);
        }

        return $result;
    }

    /**
     * Desglose por Level2 → Level3, mensual.
     * Acumula by_branch con prorrateo por superficie (igual que buildByParcel).
     */
    private function buildByLevel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, array $months): array
    {
        $monthIds = array_column($months, 'id');

        $result = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToLevel = function (&$bucket, string $key, string $l2, string $l3, int|string $branchId, int|string $parcelId, int|string $crId, float $amount, float $workdays = 0) {
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['amount' => 0.0, 'workdays' => 0.0, 'level2' => $l2, 'level3' => $l3, 'by_branch' => [], 'by_parcel' => [], 'by_company_reason' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $bKey = (string) ($branchId ?: 0);
            if (!isset($bucket[$key]['by_branch'][$bKey])) {
                $bucket[$key]['by_branch'][$bKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_branch'][$bKey]['amount']   += $amount;
            $bucket[$key]['by_branch'][$bKey]['workdays'] += $workdays;

            $pKey = (string) ($parcelId ?: 0);
            if (!isset($bucket[$key]['by_parcel'][$pKey])) {
                $bucket[$key]['by_parcel'][$pKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_parcel'][$pKey]['amount']   += $amount;
            $bucket[$key]['by_parcel'][$pKey]['workdays'] += $workdays;

            $crKey = (string) ($crId ?: 0);
            if (!isset($bucket[$key]['by_company_reason'][$crKey])) {
                $bucket[$key]['by_company_reason'][$crKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_company_reason'][$crKey]['amount']   += $amount;
            $bucket[$key]['by_company_reason'][$crKey]['workdays'] += $workdays;
        };

        // Helper: prorate a record through its CCs and call $addToLevel for each slice
        $prorate = function ($record, $ccGrouped, string $l2, string $l3, float $totalAmount, float $workdays = 0) use (&$result, &$addToLevel) {
            $mid       = $record->month_id ?? null;
            $key       = $l2 . '||' . $l3;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs      = count($ccs);

            if ($nCCs === 0) {
                if ($mid && isset($result[$mid])) {
                    $addToLevel($result[$mid], $key, $l2, $l3, 0, 0, 0, $totalAmount, $workdays);
                }
                $addToLevel($result['all'], $key, $l2, $l3, 0, 0, 0, $totalAmount, $workdays);
                return;
            }

            foreach ($ccs as $cc) {
                $branchId = $cc->branch_id ?? 0;
                $parcelId = $cc->parcel_id ?? 0;
                $crId     = $cc->company_reason_id ?? 0;
                $surf     = (float) $cc->surface;
                $prop     = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $amtSlice = $totalAmount * $prop;
                $wdSlice  = $workdays * $prop;

                if ($mid && isset($result[$mid])) {
                    $addToLevel($result[$mid], $key, $l2, $l3, $branchId, $parcelId, $crId, $amtSlice, $wdSlice);
                }
                $addToLevel($result['all'], $key, $l2, $l3, $branchId, $parcelId, $crId, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $l2    = $y->level2_name ?? 'Sin Clasificar';
            $l3    = $y->level3_name ?? 'Sin Clasificar';
            $total = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $wd    = (float) ($y->workdays ?? 0);
            $prorate($y, $yieldCCs, $l2, $l3, $total, $wd);
        }

        foreach ($bonuses as $b) {
            $l2 = $b->level2_name ?? 'Sin Clasificar';
            $l3 = $b->level3_name ?? 'Sin Clasificar';
            $prorate($b, $bonusCCs, $l2, $l3, (float) ($b->amount ?? 0));
        }

        foreach ($overtimes as $o) {
            $l2 = $o->level2_name ?? 'Sin Clasificar';
            $l3 = $o->level3_name ?? 'Sin Clasificar';
            $prorate($o, $overtimeCCs, $l2, $l3, (float) ($o->amount ?? 0));
        }

        // Convertir cada mes a array ordenado por level2+level3
        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp($a['level2'] . $a['level3'], $b['level2'] . $b['level3']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Prorrateo por superficie: distribuye el monto de cada registro
     * entre sus CCs usando (surface_CC / total_surface_CCs) * monto.
     * Agrupado por parcela y mes.
     */
    private function buildByParcel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $parcels, array $months): array
    {
        $monthIds = array_column($months, 'id');
        $result   = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToParcel = function (&$bucket, int $parcelId, string $parcelName, int|string $branchId, int|string $crId, float $amount, float $workdays = 0) {
            $key = $parcelId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['parcel_id' => $parcelId, 'parcel_name' => $parcelName, 'amount' => 0.0, 'workdays' => 0.0, 'by_branch' => [], 'by_company_reason' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $bKey = (string) ($branchId ?: 0);
            if (!isset($bucket[$key]['by_branch'][$bKey])) {
                $bucket[$key]['by_branch'][$bKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_branch'][$bKey]['amount']   += $amount;
            $bucket[$key]['by_branch'][$bKey]['workdays'] += $workdays;

            $crKey = (string) ($crId ?: 0);
            if (!isset($bucket[$key]['by_company_reason'][$crKey])) {
                $bucket[$key]['by_company_reason'][$crKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_company_reason'][$crKey]['amount']   += $amount;
            $bucket[$key]['by_company_reason'][$crKey]['workdays'] += $workdays;
        };

        // Función genérica de prorrateo
        $prorate = function ($record, $ccGrouped, float $totalAmount, float $workdays = 0) use (&$result, &$addToParcel, $parcels) {
            $mid       = $record->month_id ?? null;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');

            foreach ($ccs as $cc) {
                $parcelId   = $cc->parcel_id;
                $parcelName = $parcels[$parcelId] ?? 'Sin Parcela';
                $branchId   = $cc->branch_id ?? 0;
                $crId       = $cc->company_reason_id ?? 0;
                $surf       = (float) $cc->surface;

                if ($totalSurf > 0) {
                    $proportion = $surf / $totalSurf;
                } else {
                    $proportion = count($ccs) > 0 ? 1 / count($ccs) : 1;
                }

                $amtSlice = $totalAmount * $proportion;
                $wdSlice  = $workdays * $proportion;

                if ($mid && isset($result[$mid])) {
                    $addToParcel($result[$mid], $parcelId, $parcelName, $branchId, $crId, $amtSlice, $wdSlice);
                }
                $addToParcel($result['all'], $parcelId, $parcelName, $branchId, $crId, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $total = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $wd    = (float) ($y->workdays ?? 0);
            $prorate($y, $yieldCCs, $total, $wd);
        }

        foreach ($bonuses as $b) {
            $prorate($b, $bonusCCs, (float) ($b->amount ?? 0));
        }

        foreach ($overtimes as $o) {
            $prorate($o, $overtimeCCs, (float) ($o->amount ?? 0));
        }

        // Ordenar por parcela
        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp($a['parcel_name'], $b['parcel_name']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Prorrateo por superficie agrupado por sucursal.
     * Misma lógica que buildByParcel pero usando branch_id del CC.
     */
    private function buildByBranch($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $branchNames, array $months): array
    {
        $monthIds = array_column($months, 'id');
        $result   = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToBranch = function (&$bucket, int|string $branchId, string $branchName, int|string $parcelId, int|string $crId, float $amount, float $workdays = 0) {
            $key = $branchId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['branch_id' => $branchId, 'branch_name' => $branchName, 'amount' => 0.0, 'workdays' => 0.0, 'by_parcel' => [], 'by_company_reason' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $pKey = (string) ($parcelId ?: 0);
            if (!isset($bucket[$key]['by_parcel'][$pKey])) {
                $bucket[$key]['by_parcel'][$pKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_parcel'][$pKey]['amount']   += $amount;
            $bucket[$key]['by_parcel'][$pKey]['workdays'] += $workdays;

            $crKey = (string) ($crId ?: 0);
            if (!isset($bucket[$key]['by_company_reason'][$crKey])) {
                $bucket[$key]['by_company_reason'][$crKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_company_reason'][$crKey]['amount']   += $amount;
            $bucket[$key]['by_company_reason'][$crKey]['workdays'] += $workdays;
        };

        $prorate = function ($record, $ccGrouped, float $totalAmount, float $workdays = 0) use (&$result, &$addToBranch, $branchNames) {
            $mid       = $record->month_id ?? null;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');

            foreach ($ccs as $cc) {
                $branchId   = $cc->branch_id ?? 0;
                $branchName = $branchId ? ($branchNames[$branchId] ?? 'Sin Sucursal') : 'Sin Sucursal';
                $crId       = $cc->company_reason_id ?? 0;
                $surf       = (float) $cc->surface;

                if ($totalSurf > 0) {
                    $proportion = $surf / $totalSurf;
                } else {
                    $proportion = count($ccs) > 0 ? 1 / count($ccs) : 1;
                }

                $amtSlice = $totalAmount * $proportion;
                $wdSlice  = $workdays * $proportion;

                if ($mid && isset($result[$mid])) {
                    $addToBranch($result[$mid], $branchId, $branchName, $cc->parcel_id ?? 0, $crId, $amtSlice, $wdSlice);
                }
                $addToBranch($result['all'], $branchId, $branchName, $cc->parcel_id ?? 0, $crId, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $total = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $wd    = (float) ($y->workdays ?? 0);
            $prorate($y, $yieldCCs, $total, $wd);
        }
        foreach ($bonuses as $b) {
            $prorate($b, $bonusCCs, (float) ($b->amount ?? 0));
        }
        foreach ($overtimes as $o) {
            $prorate($o, $overtimeCCs, (float) ($o->amount ?? 0));
        }

        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp($a['branch_name'], $b['branch_name']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Desglose por Razón Social del CC.
     * Cada CC tiene company_reason_id. Prorratea por superficie igual que buildByBranch.
     * Retorna: [ month_id => [rows...], 'all' => [rows...] ]
     */
    private function buildByCompanyReason($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $companyReasonNames, array $months): array
    {
        $monthIds = array_column($months, 'id');
        $result   = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToRS = function (&$bucket, int|string $crId, string $crName, int|string $branchId, int|string $parcelId, float $amount, float $workdays = 0) {
            $key = (string) $crId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['company_reason_id' => $crId, 'company_reason_name' => $crName, 'amount' => 0.0, 'workdays' => 0.0, 'by_branch' => [], 'by_parcel' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $bKey = (string) ($branchId ?: 0);
            if (!isset($bucket[$key]['by_branch'][$bKey])) {
                $bucket[$key]['by_branch'][$bKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_branch'][$bKey]['amount']   += $amount;
            $bucket[$key]['by_branch'][$bKey]['workdays'] += $workdays;

            $pKey = (string) ($parcelId ?: 0);
            if (!isset($bucket[$key]['by_parcel'][$pKey])) {
                $bucket[$key]['by_parcel'][$pKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_parcel'][$pKey]['amount']   += $amount;
            $bucket[$key]['by_parcel'][$pKey]['workdays'] += $workdays;
        };

        $prorate = function ($record, $ccGrouped, float $totalAmount, float $workdays = 0) use (&$result, &$addToRS, $companyReasonNames) {
            $mid       = $record->month_id ?? null;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs      = count($ccs);

            if ($nCCs === 0) return;

            foreach ($ccs as $cc) {
                $crId     = $cc->company_reason_id ?? 0;
                $crName   = $crId ? ($companyReasonNames[$crId] ?? 'Sin Razón Social') : 'Sin Razón Social';
                $branchId = $cc->branch_id ?? 0;
                $parcelId = $cc->parcel_id ?? 0;
                $surf     = (float) $cc->surface;
                $prop     = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $amtSlice = $totalAmount * $prop;
                $wdSlice  = $workdays * $prop;

                if ($mid && isset($result[$mid])) {
                    $addToRS($result[$mid], $crId, $crName, $branchId, $parcelId, $amtSlice, $wdSlice);
                }
                $addToRS($result['all'], $crId, $crName, $branchId, $parcelId, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $total = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $wd    = (float) ($y->workdays ?? 0);
            $prorate($y, $yieldCCs, $total, $wd);
        }
        foreach ($bonuses as $b) {
            $prorate($b, $bonusCCs, (float) ($b->amount ?? 0));
        }
        foreach ($overtimes as $o) {
            $prorate($o, $overtimeCCs, (float) ($o->amount ?? 0));
        }

        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp($a['company_reason_name'], $b['company_reason_name']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Distribución de montos por RS contratante → Parcelas donde trabajaron sus empleados.
     * Permite identificar trabajo cruzado entre razones sociales (cross-billing).
     * employer_RS = contract.company_reason_id del trabajador
     * parcel_RS   = cost_center.company_reason_id del CC donde registró trabajo
     */
    private function buildByRSParcelDetail(
        $yields, $yieldCCs,
        $bonuses, $bonusCCs,
        $overtimes, $overtimeCCs,
        $employeeCRMap,    // employee_id → company_reason_id
        $contractCRMap,    // contract_id → company_reason_id
        $companyReasonNames,
        $parcels,
        array $months
    ): array {
        $monthIds = array_column($months, 'id');
        $result   = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $add = function (&$bucket, int|string $employerCRId, string $employerCRName, int|string $parcelId, string $parcelName, int|string $parcelCRId, string $parcelCRName, float $amount, float $workdays = 0) {
            $crKey = (string) $employerCRId;
            if (!isset($bucket[$crKey])) {
                $bucket[$crKey] = [
                    'company_reason_id'   => $employerCRId,
                    'company_reason_name' => $employerCRName,
                    'total_amount'        => 0.0,
                    'total_workdays'      => 0.0,
                    'parcels'             => [],
                ];
            }
            $bucket[$crKey]['total_amount']   += $amount;
            $bucket[$crKey]['total_workdays'] += $workdays;

            $pKey = (string) $parcelId;
            if (!isset($bucket[$crKey]['parcels'][$pKey])) {
                $bucket[$crKey]['parcels'][$pKey] = [
                    'parcel_id'                  => $parcelId,
                    'parcel_name'                => $parcelName,
                    'parcel_company_reason_id'   => $parcelCRId,
                    'parcel_company_reason_name' => $parcelCRName,
                    'amount'                     => 0.0,
                    'workdays'                   => 0.0,
                ];
            }
            $bucket[$crKey]['parcels'][$pKey]['amount']   += $amount;
            $bucket[$crKey]['parcels'][$pKey]['workdays'] += $workdays;
        };

        $prorate = function ($record, $ccGrouped, float $totalAmount, float $workdays, int|string $employerCRId) use (&$result, &$add, $companyReasonNames, $parcels) {
            // Usar fallback en vez de descartar — así el total siempre cuadra
            if (!$employerCRId) {
                $employerCRId   = 0;
                $employerCRName = 'Sin RS contratante';
            } else {
                $employerCRName = $companyReasonNames[$employerCRId] ?? 'Sin RS contratante';
            }
            $mid            = $record->month_id ?? null;
            $ccs            = $ccGrouped->get($record->id, collect());
            // Solo CCs con parcel_id para no perder monto en la proración
            $ccsWithParcel  = $ccs->filter(fn($cc) => !empty($cc->parcel_id));
            $totalSurf      = $ccsWithParcel->sum('surface');
            $nCCs           = count($ccsWithParcel);
            if ($nCCs === 0) return;

            foreach ($ccsWithParcel as $cc) {
                $parcelId    = $cc->parcel_id;
                $parcelName  = $parcels[$parcelId] ?? 'Sin Parcela';
                $parcelCRId  = $cc->company_reason_id ?? 0;
                $parcelCRName = $parcelCRId ? ($companyReasonNames[$parcelCRId] ?? 'Sin RS') : 'Sin RS';
                $surf        = (float) $cc->surface;
                $prop        = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $amtSlice = $totalAmount * $prop;
                $wdSlice  = $workdays * $prop;

                if ($mid && isset($result[$mid])) {
                    $add($result[$mid], $employerCRId, $employerCRName, $parcelId, $parcelName, $parcelCRId, $parcelCRName, $amtSlice, $wdSlice);
                }
                $add($result['all'], $employerCRId, $employerCRName, $parcelId, $parcelName, $parcelCRId, $parcelCRName, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $employerCRId = $employeeCRMap[$y->employee_id] ?? 0;
            $total = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $wd    = (float) ($y->workdays ?? 0);
            $prorate($y, $yieldCCs, $total, $wd, $employerCRId);
        }
        foreach ($bonuses as $b) {
            $employerCRId = $contractCRMap[$b->contract_id] ?? 0;
            $prorate($b, $bonusCCs, (float) ($b->amount ?? 0), 0, $employerCRId);
        }
        foreach ($overtimes as $o) {
            $employerCRId = $contractCRMap[$o->contract_id] ?? 0;
            $prorate($o, $overtimeCCs, (float) ($o->amount ?? 0), 0, $employerCRId);
        }

        // Formatear: parcelas como array ordenado descendente, agregar porcentaje
        $format = function (array $bucket): array {
            $rows = [];
            foreach ($bucket as $crData) {
                $parcelRows = array_values($crData['parcels']);
                $total      = $crData['total_amount'];
                usort($parcelRows, fn($a, $b) => $b['amount'] <=> $a['amount']);
                foreach ($parcelRows as &$p) {
                    $p['percentage'] = $total > 0 ? round(($p['amount'] / $total) * 100, 1) : 0;
                }
                $rows[] = [
                    'company_reason_id'   => $crData['company_reason_id'],
                    'company_reason_name' => $crData['company_reason_name'],
                    'total_amount'        => round($crData['total_amount']),
                    'total_workdays'      => $crData['total_workdays'],
                    'parcels'             => $parcelRows,
                ];
            }
            usort($rows, fn($a, $b) => strcmp($a['company_reason_name'], $b['company_reason_name']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Desglose por trato (labor_rate), solo registros con payment_type='trato'.
     * Acumula cantidad y monto prorated por superficie de CC.
     * Retorna: [ month_id => [rows...], 'all' => [rows...] ]
     */
    private function buildByTrato($yields, $yieldCCs, array $months): array
    {
        $monthIds = array_column($months, 'id');
        $result   = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToTrato = function (&$bucket, int|string $tratoId, string $tratoName, int $price, int|string $branchId, int|string $parcelId, int|string $crId, float $quantity, float $amount) {
            $key = (string) $tratoId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'trato_id'   => $tratoId,
                    'trato_name' => $tratoName,
                    'price'      => $price,
                    'quantity'   => 0.0,
                    'amount'     => 0.0,
                    'by_branch'  => [],
                    'by_parcel'  => [],
                    'by_company_reason' => [],
                ];
            }
            $bucket[$key]['quantity'] += $quantity;
            $bucket[$key]['amount']   += $amount;

            $bKey = (string) ($branchId ?: 0);
            if (!isset($bucket[$key]['by_branch'][$bKey])) {
                $bucket[$key]['by_branch'][$bKey] = ['quantity' => 0.0, 'amount' => 0.0];
            }
            $bucket[$key]['by_branch'][$bKey]['quantity'] += $quantity;
            $bucket[$key]['by_branch'][$bKey]['amount']   += $amount;

            $pKey = (string) ($parcelId ?: 0);
            if (!isset($bucket[$key]['by_parcel'][$pKey])) {
                $bucket[$key]['by_parcel'][$pKey] = ['quantity' => 0.0, 'amount' => 0.0];
            }
            $bucket[$key]['by_parcel'][$pKey]['quantity'] += $quantity;
            $bucket[$key]['by_parcel'][$pKey]['amount']   += $amount;

            $crKey = (string) ($crId ?: 0);
            if (!isset($bucket[$key]['by_company_reason'][$crKey])) {
                $bucket[$key]['by_company_reason'][$crKey] = ['quantity' => 0.0, 'amount' => 0.0];
            }
            $bucket[$key]['by_company_reason'][$crKey]['quantity'] += $quantity;
            $bucket[$key]['by_company_reason'][$crKey]['amount']   += $amount;
        };

        foreach ($yields as $y) {
            if (($y->payment_type ?? '') !== 'trato') continue;

            $tratoId   = $y->labor_rate_id ?? 0;
            $tratoName = $y->labor_rate_name ?? 'Sin Nombre';
            $price     = (int) ($y->rate ?? 0);
            $mid       = $y->month_id;

            $ccs       = $yieldCCs->get($y->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs      = count($ccs);

            if ($nCCs === 0) {
                $qty = (float) ($y->quantity ?? 0);
                $amt = (float) ($y->amount ?? 0);
                if ($mid && isset($result[$mid])) {
                    $addToTrato($result[$mid], $tratoId, $tratoName, $price, 0, 0, 0, $qty, $amt);
                }
                $addToTrato($result['all'], $tratoId, $tratoName, $price, 0, 0, 0, $qty, $amt);
                continue;
            }

            foreach ($ccs as $cc) {
                $branchId = $cc->branch_id ?? 0;
                $parcelId = $cc->parcel_id ?? 0;
                $crId     = $cc->company_reason_id ?? 0;
                $surf     = (float) $cc->surface;
                $prop     = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $qty = (float) ($y->quantity ?? 0) * $prop;
                $amt = (float) ($y->amount ?? 0) * $prop;

                if ($mid && isset($result[$mid])) {
                    $addToTrato($result[$mid], $tratoId, $tratoName, $price, $branchId, $parcelId, $crId, $qty, $amt);
                }
                $addToTrato($result['all'], $tratoId, $tratoName, $price, $branchId, $parcelId, $crId, $qty, $amt);
            }
        }

        // Ordenar por nombre
        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp($a['trato_name'], $b['trato_name']));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }

    /**
     * Totales acumulados de la temporada.
     */
    private function buildSeasonTotals(array $byMonth): array
    {
        $totalAmount   = 0.0;
        $totalWorkdays = 0.0;
        foreach ($byMonth as $m) {
            $totalAmount   += $m['amount'];
            $totalWorkdays += $m['workdays'];
        }
        return [
            'amount'   => $totalAmount,
            'workdays' => $totalWorkdays,
        ];
    }

    /**
     * Datos para los gráficos de barras (Chart.js).
     */
    private function buildChartData(array $byMonth, array $months): array
    {
        $labels   = [];
        $amounts  = [];
        $workdays = [];

        foreach ($months as $m) {
            $labels[]   = $m['short_name'];
            $amounts[]  = $byMonth[$m['id']]['amount'] ?? 0;
            $workdays[] = round($byMonth[$m['id']]['workdays'] ?? 0, 2);
        }

        return compact('labels', 'amounts', 'workdays');
    }

    /**
     * Reporte de labores distribuidas por centro de costo.
     * Retorna array indexado por 'all' + month_id.
     * Cada bucket es un array de filas ordenadas.
     */
    private function buildByCostCenter($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, array $months, $ccDetails): array
    {
        $monthIds = array_column($months, 'id');

        $result = ['all' => []];
        foreach ($monthIds as $mid) {
            $result[$mid] = [];
        }

        $addToCC = function (array &$bucket, int|string $ccId, string $costCenter, string $branch, string $parcel,
                             int|string|null $branchId, int|string|null $parcelId,
                             int|string|null $companyReasonId,
                             string $companyReasonName,
                             string $laborType, int|string $laborTypeId, string $level3, string $level2, string $level1,
                             float $amount, float $workdays) {
            $key = (string)$ccId . '||' . (string)$laborTypeId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'cost_center_id'      => $ccId,
                    'cost_center'         => $costCenter,
                    'branch_id'           => $branchId,
                    'parcel_id'           => $parcelId,
                    'branch'              => $branch,
                    'parcel'              => $parcel,
                    'company_reason_id'   => $companyReasonId,
                    'company_reason_name' => $companyReasonName,
                    'labor_type'          => $laborType,
                    'level3'              => $level3,
                    'level2'              => $level2,
                    'level1'              => $level1,
                    'workdays'            => 0.0,
                    'amount'              => 0.0,
                ];
            }
            $bucket[$key]['workdays'] += $workdays;
            $bucket[$key]['amount']   += $amount;
        };

        $prorate = function ($record, $ccGrouped, float $totalAmt, float $wd) use (&$result, &$addToCC, $ccDetails) {
            $mid         = $record->month_id ?? null;
            $ccs         = $ccGrouped->get($record->id, collect());
            $totalSurf   = $ccs->sum('surface');
            $nCCs        = count($ccs);
            $laborType   = $record->labor_type_name ?? 'Sin Labor';
            $laborTypeId = $record->labor_type_id   ?? 0;
            $level3      = $record->level3_name     ?? 'Sin Clasificar';
            $level2      = $record->level2_name     ?? 'Sin Clasificar';
            $level1      = $record->level1_name     ?? 'Sin Clasificar';

            if ($nCCs === 0) {
                if ($mid !== null && isset($result[$mid])) {
                    $addToCC($result[$mid], 0, 'Sin CC', 'Sin Sucursal', 'Sin Parcela', null, null, null, 'Sin RS', $laborType, $laborTypeId, $level3, $level2, $level1, $totalAmt, $wd);
                }
                $addToCC($result['all'], 0, 'Sin CC', 'Sin Sucursal', 'Sin Parcela', null, null, null, 'Sin RS', $laborType, $laborTypeId, $level3, $level2, $level1, $totalAmt, $wd);
                return;
            }

            foreach ($ccs as $cc) {
                $ccId             = $cc->cost_center_id;
                $detail           = $ccDetails->get($ccId);
                $ccName            = $detail?->name               ?? ('CC ' . $ccId);
                $branch            = $detail?->branch_name        ?? 'Sin Sucursal';
                $parcel            = $detail?->parcel_name        ?? 'Sin Parcela';
                $companyReasonId   = $detail?->company_reason_id  ?? null;
                $companyReasonName = $detail?->company_reason_name ?? 'Sin RS';
                $branchId          = $detail?->branch_id          ?? null;
                $parcelId          = $detail?->parcel_id          ?? null;
                $surf             = (float) $cc->surface;
                $prop             = $totalSurf > 0 ? $surf / $totalSurf : 1.0 / $nCCs;
                $amtSlice         = $totalAmt * $prop;
                $wdSlice          = $wd * $prop;

                if ($mid !== null && isset($result[$mid])) {
                    $addToCC($result[$mid], $ccId, $ccName, $branch, $parcel, $branchId, $parcelId, $companyReasonId, $companyReasonName, $laborType, $laborTypeId, $level3, $level2, $level1, $amtSlice, $wdSlice);
                }
                $addToCC($result['all'], $ccId, $ccName, $branch, $parcel, $branchId, $parcelId, $companyReasonId, $companyReasonName, $laborType, $laborTypeId, $level3, $level2, $level1, $amtSlice, $wdSlice);
            }
        };

        foreach ($yields as $y) {
            $totalAmt = (float)(($y->amount ?? 0) + ($y->bonus_amount ?? 0) + ($y->target_price_bonus ?? 0));
            $prorate($y, $yieldCCs, $totalAmt, (float)($y->workdays ?? 0));
        }

        foreach ($bonuses as $b) {
            $prorate($b, $bonusCCs, (float)($b->amount ?? 0), 0.0);
        }

        foreach ($overtimes as $o) {
            $prorate($o, $overtimeCCs, (float)($o->amount ?? 0), 0.0);
        }

        $format = function (array $bucket): array {
            $rows = array_values($bucket);
            usort($rows, fn($a, $b) => strcmp(
                ($a['level1'] ?? '') . ($a['level2'] ?? '') . ($a['level3'] ?? '') . ($a['labor_type'] ?? '') . ($a['cost_center'] ?? ''),
                ($b['level1'] ?? '') . ($b['level2'] ?? '') . ($b['level3'] ?? '') . ($b['labor_type'] ?? '') . ($b['cost_center'] ?? '')
            ));
            return $rows;
        };

        $formatted = [];
        foreach ($result as $mid => $bucket) {
            $formatted[$mid] = $format($bucket);
        }
        return $formatted;
    }
}
