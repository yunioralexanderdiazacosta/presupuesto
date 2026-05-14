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
            ->leftJoin('labor_rates as lr', 'dy.labor_rate_id', '=', 'lr.id')
            ->where('dy.team_id', $teamId)
            ->where('dy.season_id', $seasonId)
            ->select(
                'dy.id',
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
                'l2.name as level2_name'
            )
            ->get();

        // Pivot CC de yields
        $yieldIds = $yields->pluck('id');
        $yieldCCs = $yieldIds->isEmpty() ? collect() : DB::table('daily_yield_cost_center as dycc')
            ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
            ->whereIn('dycc.daily_yield_id', $yieldIds)
            ->select('dycc.daily_yield_id', 'dycc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id')
            ->get()
            ->groupBy('daily_yield_id');

        // ============================================================
        // 2. MONTHLY BONUSES — carga única
        // ============================================================
        $bonuses = DB::table('monthly_bonuses as mb')
            ->join('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->where('mb.team_id', $teamId)
            ->whereIn('mb.contract_id', $contractIds)
            ->select(
                'mb.id',
                'mb.month_id',
                'mb.amount',
                'l3.id as level3_id',
                'l3.name as level3_name',
                'l2.id as level2_id',
                'l2.name as level2_name'
            )
            ->get();

        $bonusIds = $bonuses->pluck('id');
        $bonusCCs = $bonusIds->isEmpty() ? collect() : DB::table('monthly_bonus_cost_centers as mbcc')
            ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
            ->whereIn('mbcc.monthly_bonus_id', $bonusIds)
            ->select('mbcc.monthly_bonus_id', 'mbcc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id')
            ->get()
            ->groupBy('monthly_bonus_id');

        // ============================================================
        // 3. OVERTIME HOURS — carga única
        // ============================================================
        $overtimes = DB::table('overtime_hours as oh')
            ->join('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->where('oh.team_id', $teamId)
            ->whereIn('oh.contract_id', $contractIds)
            ->select(
                'oh.id',
                'oh.month_id',
                DB::raw('ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot) as amount'),
                'l3.id as level3_id',
                'l3.name as level3_name',
                'l2.id as level2_id',
                'l2.name as level2_name'
            )
            ->get();

        $overtimeIds = $overtimes->pluck('id');
        $overtimeCCs = $overtimeIds->isEmpty() ? collect() : DB::table('overtime_hour_cost_centers as ohcc')
            ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
            ->whereIn('ohcc.overtime_hour_id', $overtimeIds)
            ->select('ohcc.overtime_hour_id', 'ohcc.cost_center_id', 'cc.surface', 'cc.parcel_id', 'cc.branch_id')
            ->get()
            ->groupBy('overtime_hour_id');

        // ============================================================
        // Procesar todas las métricas
        // ============================================================
        $byMonth      = $this->buildByMonth($yields, $bonuses, $overtimes, $months);
        $byLevel      = $this->buildByLevel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $months);
        $byParcel     = $this->buildByParcel($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $parcels, $months);
        $byBranch     = $this->buildByBranch($yields, $yieldCCs, $bonuses, $bonusCCs, $overtimes, $overtimeCCs, $branchNames, $months);
        $byTrato      = $this->buildByTrato($yields, $yieldCCs, $months);
        $seasonTotals = $this->buildSeasonTotals($byMonth);
        $chartData    = $this->buildChartData($byMonth, $months);

        return Inertia::render('PayrollDashboard', [
            'byMonth'        => $byMonth,
            'byLevel'        => $byLevel,
            'byParcel'       => $byParcel,
            'byBranch'       => $byBranch,
            'byTrato'        => $byTrato,
            'seasonTotals'   => $seasonTotals,
            'chartData'      => $chartData,
            'months'         => $months,
            'seasonStartMonth' => $startMonthId,
            'branches'       => $branches,
            'parcelBranchMap' => $parcelBranchMap,
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

        $addToLevel = function (&$bucket, string $key, string $l2, string $l3, int|string $branchId, int|string $parcelId, float $amount, float $workdays = 0) {
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['amount' => 0.0, 'workdays' => 0.0, 'level2' => $l2, 'level3' => $l3, 'by_branch' => [], 'by_parcel' => []];
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

        // Helper: prorate a record through its CCs and call $addToLevel for each slice
        $prorate = function ($record, $ccGrouped, string $l2, string $l3, float $totalAmount, float $workdays = 0) use (&$result, &$addToLevel) {
            $mid       = $record->month_id ?? null;
            $key       = $l2 . '||' . $l3;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs      = count($ccs);

            if ($nCCs === 0) {
                // Sin CCs: acumular todo bajo branch 0 y parcel 0
                if ($mid && isset($result[$mid])) {
                    $addToLevel($result[$mid], $key, $l2, $l3, 0, 0, $totalAmount, $workdays);
                }
                $addToLevel($result['all'], $key, $l2, $l3, 0, 0, $totalAmount, $workdays);
                return;
            }

            foreach ($ccs as $cc) {
                $branchId = $cc->branch_id ?? 0;
                $parcelId = $cc->parcel_id ?? 0;
                $surf     = (float) $cc->surface;
                $prop     = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $amtSlice = $totalAmount * $prop;
                $wdSlice  = $workdays * $prop;

                if ($mid && isset($result[$mid])) {
                    $addToLevel($result[$mid], $key, $l2, $l3, $branchId, $parcelId, $amtSlice, $wdSlice);
                }
                $addToLevel($result['all'], $key, $l2, $l3, $branchId, $parcelId, $amtSlice, $wdSlice);
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

        $addToParcel = function (&$bucket, int $parcelId, string $parcelName, int|string $branchId, float $amount, float $workdays = 0) {
            $key = $parcelId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['parcel_id' => $parcelId, 'parcel_name' => $parcelName, 'amount' => 0.0, 'workdays' => 0.0, 'by_branch' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $bKey = (string) ($branchId ?: 0);
            if (!isset($bucket[$key]['by_branch'][$bKey])) {
                $bucket[$key]['by_branch'][$bKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_branch'][$bKey]['amount']   += $amount;
            $bucket[$key]['by_branch'][$bKey]['workdays'] += $workdays;
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
                $surf       = (float) $cc->surface;

                if ($totalSurf > 0) {
                    $proportion = $surf / $totalSurf;
                } else {
                    $proportion = count($ccs) > 0 ? 1 / count($ccs) : 1;
                }

                $amtSlice = $totalAmount * $proportion;
                $wdSlice  = $workdays * $proportion;

                if ($mid && isset($result[$mid])) {
                    $addToParcel($result[$mid], $parcelId, $parcelName, $branchId, $amtSlice, $wdSlice);
                }
                $addToParcel($result['all'], $parcelId, $parcelName, $branchId, $amtSlice, $wdSlice);
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

        $addToBranch = function (&$bucket, int|string $branchId, string $branchName, int|string $parcelId, float $amount, float $workdays = 0) {
            $key = $branchId;
            if (!isset($bucket[$key])) {
                $bucket[$key] = ['branch_id' => $branchId, 'branch_name' => $branchName, 'amount' => 0.0, 'workdays' => 0.0, 'by_parcel' => []];
            }
            $bucket[$key]['amount']   += $amount;
            $bucket[$key]['workdays'] += $workdays;

            $pKey = (string) ($parcelId ?: 0);
            if (!isset($bucket[$key]['by_parcel'][$pKey])) {
                $bucket[$key]['by_parcel'][$pKey] = ['amount' => 0.0, 'workdays' => 0.0];
            }
            $bucket[$key]['by_parcel'][$pKey]['amount']   += $amount;
            $bucket[$key]['by_parcel'][$pKey]['workdays'] += $workdays;
        };

        $prorate = function ($record, $ccGrouped, float $totalAmount, float $workdays = 0) use (&$result, &$addToBranch, $branchNames) {
            $mid       = $record->month_id ?? null;
            $ccs       = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');

            foreach ($ccs as $cc) {
                $branchId   = $cc->branch_id ?? 0;
                $branchName = $branchId ? ($branchNames[$branchId] ?? 'Sin Sucursal') : 'Sin Sucursal';
                $surf       = (float) $cc->surface;

                if ($totalSurf > 0) {
                    $proportion = $surf / $totalSurf;
                } else {
                    $proportion = count($ccs) > 0 ? 1 / count($ccs) : 1;
                }

                $amtSlice = $totalAmount * $proportion;
                $wdSlice  = $workdays * $proportion;

                if ($mid && isset($result[$mid])) {
                    $addToBranch($result[$mid], $branchId, $branchName, $cc->parcel_id ?? 0, $amtSlice, $wdSlice);
                }
                $addToBranch($result['all'], $branchId, $branchName, $cc->parcel_id ?? 0, $amtSlice, $wdSlice);
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

        $addToTrato = function (&$bucket, int|string $tratoId, string $tratoName, int $price, int|string $branchId, int|string $parcelId, float $quantity, float $amount) {
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
                    $addToTrato($result[$mid], $tratoId, $tratoName, $price, 0, 0, $qty, $amt);
                }
                $addToTrato($result['all'], $tratoId, $tratoName, $price, 0, 0, $qty, $amt);
                continue;
            }

            foreach ($ccs as $cc) {
                $branchId = $cc->branch_id ?? 0;
                $parcelId = $cc->parcel_id ?? 0;
                $surf     = (float) $cc->surface;
                $prop     = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;

                $qty = (float) ($y->quantity ?? 0) * $prop;
                $amt = (float) ($y->amount ?? 0) * $prop;

                if ($mid && isset($result[$mid])) {
                    $addToTrato($result[$mid], $tratoId, $tratoName, $price, $branchId, $parcelId, $qty, $amt);
                }
                $addToTrato($result['all'], $tratoId, $tratoName, $price, $branchId, $parcelId, $qty, $amt);
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
}
