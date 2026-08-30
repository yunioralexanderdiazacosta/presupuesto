<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Season;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InvestmentDashboardController extends Controller
{
    public function index()
    {
        $season_id = session('season_id');
        $user = Auth::user();
        $team_id = $user->team_id;

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        // Obtener dollar_price del admin del equipo
        $adminUser = \App\Models\User::where('team_id', $team_id)
            ->role('Admin')
            ->first();
        $dollarPrice = (float) ($adminUser?->dollar_price ?? 970);

        // Obtener información de la temporada
        $season = Season::with('month')->find($season_id);
        $startMonthId = $season->month_id ?? 1;

        $months = $this->generateMonthsArray($startMonthId);

        // Presupuesto real: calculado UNA sola vez a partir de los productos de los 8 módulos
        // (agroquímicos, fertilizantes, mano de obra, servicios, insumos, cosecha, administración,
        // campos) vinculados a cada inversión mediante investment_id.
        $budgetedData = $this->getBudgetedByInvestment($season_id, $team_id);

        return Inertia::render('InvestmentDashboard', [
            'dollarPrice' => $dollarPrice,
            'isAdmin'     => $user->hasRole('Admin'),
            'summary'     => $this->getSummary($season_id, $team_id, $budgetedData),
            'monthlyComparison' => $this->getMonthlyComparison($season_id, $team_id, $months, $budgetedData),
            'investmentDetails' => $this->getInvestmentDetails($season_id, $team_id, $budgetedData),
            'byLevel3'    => $this->getByLevel3($season_id, $team_id),
            'months' => $months,
        ]);
    }

    /**
     * Presupuesto real por inversión, calculado a partir de los productos de los 8 módulos
     * de presupuesto (agroquímicos, fertilizantes, mano de obra, servicios, insumos, cosecha,
     * administración, campos) que tienen investment_id asignado.
     * Devuelve ['totals' => [investment_id => monto], 'byMonth' => [month_id (1-12) => monto]]
     */
    private function getBudgetedByInvestment($season_id, $team_id)
    {
        $totals = [];
        $byMonth = [];

        // Clave 0 = productos marcados como Inversión pero sin vincular a una inversión puntual
        $addAmount = function ($investmentId, $monthId, $amount) use (&$totals, &$byMonth) {
            if ($amount == 0) return;
            $key = $investmentId ?: 0;
            $totals[$key] = ($totals[$key] ?? 0) + $amount;
            if ($monthId) {
                $byMonth[$monthId] = ($byMonth[$monthId] ?? 0) + $amount;
            }
        };

        try {
            $inversionOperationId = DB::table('operations')
                ->whereRaw('LOWER(name) LIKE ?', ['%inversion%'])
                ->value('id');
            if (!$inversionOperationId) {
                return ['totals' => $totals, 'byMonth' => $byMonth];
            }

            $season = Season::select('month_id')->where('id', $season_id)->first();
            $currentMonth = $season->month_id ?? 1;
            $months = [];
            for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
                $months[] = (int) date('n', mktime(0, 0, 0, $x, 1));
            }

            $costCenters = CostCenter::where('season_id', $season_id)->get()->keyBy('id');
            $centerIds = $costCenters->keys();

            // Módulos con centro de costo (superficie) y dosis/cantidad/precio
            $ccModules = [
                ['model' => \App\Models\Agrochemical::class, 'table' => 'agrochemical_items', 'fk' => 'agrochemical_id', 'type' => 'agrochemical'],
                ['model' => \App\Models\Fertilizer::class,   'table' => 'fertilizer_items',   'fk' => 'fertilizer_id',   'type' => 'dose'],
                ['model' => \App\Models\ManPower::class,     'table' => 'manpower_items',      'fk' => 'man_power_id',    'type' => 'workday'],
                ['model' => \App\Models\Supply::class,       'table' => 'supply_items',        'fk' => 'supply_id',       'type' => 'quantity'],
                ['model' => \App\Models\Service::class,      'table' => 'service_items',       'fk' => 'service_id',      'type' => 'quantity'],
                ['model' => \App\Models\Harvest::class,      'table' => 'harvest_items',       'fk' => 'harvest_id',      'type' => 'quantity'],
            ];

            foreach ($ccModules as $mod) {
                $items = $mod['model']::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->where('operation_id', $inversionOperationId)
                    ->get()
                    ->keyBy('id');

                if ($items->isEmpty() || $centerIds->isEmpty()) continue;

                $rows = DB::table($mod['table'])
                    ->select($mod['fk'] . ' as item_id', 'cost_center_id', 'month_id')
                    ->whereIn($mod['fk'], $items->keys())
                    ->whereIn('cost_center_id', $centerIds)
                    ->whereIn('month_id', $months)
                    ->get();

                foreach ($rows as $row) {
                    $product = $items[$row->item_id];
                    $surface = $costCenters[$row->cost_center_id]->surface ?? 1;

                    if ($mod['type'] === 'agrochemical') {
                        $dose = (($product->unit_id == 4 && $product->unit_id_price == 3) || ($product->unit_id == 2 && $product->unit_id_price == 1)) ? ($product->dose / 1000) : $product->dose;
                        if ($product->dose_type_id == 2) {
                            $quantity = round((($product->mojamiento / 100) * $dose * $surface), 2);
                        } else {
                            $quantity = round($dose * $surface, 2);
                        }
                    } elseif ($mod['type'] === 'dose') {
                        $dose = (($product->unit_id == 4 && $product->unit_id_price == 3) || ($product->unit_id == 2 && $product->unit_id_price == 1)) ? ($product->dose / 1000) : $product->dose;
                        $quantity = round($dose * $surface, 2);
                    } elseif ($mod['type'] === 'workday') {
                        $quantity = round($product->workday * $surface, 2);
                    } else {
                        $quantity = (($product->unit_id == 4 && $product->unit_id_price == 3) || ($product->unit_id == 2 && $product->unit_id_price == 1)) ? ($product->quantity / 1000) : $product->quantity;
                        $quantity = round($quantity * $surface, 2);
                    }

                    $amount = round($product->price * $quantity, 2);
                    $addAmount($product->investment_id, $row->month_id, $amount);
                }
            }

            // Administración: sin centro de costo/superficie
            $administrations = \App\Models\Administration::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('operation_id', $inversionOperationId)
                ->get()
                ->keyBy('id');
            if ($administrations->isNotEmpty()) {
                $rows = DB::table('administration_items')
                    ->select('administration_id', 'month_id')
                    ->whereIn('administration_id', $administrations->keys())
                    ->get();
                foreach ($rows as $row) {
                    $adm = $administrations[$row->administration_id];
                    $quantity = (($adm->unit_id == 4) || ($adm->unit_id == 2)) ? ($adm->quantity / 1000) : $adm->quantity;
                    $amount = round($adm->price * $quantity, 2);
                    $addAmount($adm->investment_id, $row->month_id, $amount);
                }
            }

            // Campos: sin centro de costo/superficie
            $fields = \App\Models\Field::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('operation_id', $inversionOperationId)
                ->get()
                ->keyBy('id');
            if ($fields->isNotEmpty()) {
                $rows = DB::table('field_items')
                    ->select('field_id', 'month_id')
                    ->whereIn('field_id', $fields->keys())
                    ->whereIn('month_id', $months)
                    ->get();
                foreach ($rows as $row) {
                    $field = $fields[$row->field_id];
                    $quantity = ($field->quantity !== null && $field->quantity > 0) ? ((in_array($field->unit_id ?? null, [2, 4])) ? ($field->quantity / 1000) : $field->quantity) : 0;
                    $amount = round($field->price * $quantity, 2);
                    $addAmount($field->investment_id, $row->month_id, $amount);
                }
            }
        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getBudgetedByInvestment: ' . $e->getMessage());
        }

        return ['totals' => $totals, 'byMonth' => $byMonth];
    }

    private function generateMonthsArray($startMonthId)
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $monthId = (($startMonthId + $i - 1) % 12) + 1;
            $months[] = [
                'id' => $monthId,
                'name' => $monthNames[$monthId],
                'short_name' => substr($monthNames[$monthId], 0, 3)
            ];
        }

        return $months;
    }

    /**
     * Resumen general: Presupuestado vs Real
     */
    private function getSummary($season_id, $team_id, $budgetedData)
    {
        try {
            // Total presupuestado (suma de productos de los 8 módulos vinculados a inversiones)
            $budgetedTotal = (float) array_sum($budgetedData['totals']);

            // Total real (outflows marcados como inversión)
            $realTotal = (float) $this->getRealInvestmentsTotal($season_id, $team_id);

            // Parte del presupuesto/real que aún no está vinculada a una inversión específica
            $unassignedBudgeted = (float) ($budgetedData['totals'][0] ?? 0);
            $unassignedReal = (float) (DB::table('outflows as o')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->whereNull('o.investment_id')
                ->selectRaw('SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                    ELSE 0
                END) as total')
                ->value('total') ?? 0);

            // Contar inversiones
            $totalCount = Investment::where('season_id', $season_id)
                ->whereHas('season', fn($q) => $q->where('team_id', $team_id))
                ->count();

            // Inversiones con outflows (ejecutadas)
            $executedCount = (int) DB::table('outflows as o')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->distinct('o.investment_id')
                ->count('o.investment_id');

            $difference = $budgetedTotal - $realTotal;
            $percentageExecution = $budgetedTotal > 0 ? ($realTotal / $budgetedTotal) * 100 : 0;

            // Superficie total
            $totalSurface = (float) DB::table('cost_centers')
                ->where('season_id', $season_id)
                ->sum('surface');

            return [
                'budgeted_total' => $budgetedTotal,
                'real_total' => $realTotal,
                'unassigned_budgeted' => $unassignedBudgeted,
                'unassigned_real' => $unassignedReal,
                'difference' => $difference,
                'percentage_execution' => $percentageExecution,
                'total_count' => $totalCount,
                'executed_count' => $executedCount,
                'budgeted_per_hectare' => $totalSurface > 0 ? $budgetedTotal / $totalSurface : 0,
                'real_per_hectare' => $totalSurface > 0 ? $realTotal / $totalSurface : 0,
                'total_surface' => $totalSurface,
            ];
        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getSummary: ' . $e->getMessage());
            return [
                'budgeted_total' => 0, 'real_total' => 0, 'difference' => 0,
                'unassigned_budgeted' => 0, 'unassigned_real' => 0,
                'percentage_execution' => 0, 'total_count' => 0, 'executed_count' => 0,
                'budgeted_per_hectare' => 0, 'real_per_hectare' => 0, 'total_surface' => 0,
            ];
        }
    }

    /**
     * Comparación mensual: presupuestado vs real por mes
     */
    private function getMonthlyComparison($season_id, $team_id, $months, $budgetedData)
    {
        try {
            // Presupuestado por mes (suma real de productos vinculados a inversiones, por mes)
            $budgetedByMonth = $budgetedData['byMonth'];

            // Real por mes (outflows con operación inversión, agrupados por mes de fecha factura)
            $realByMonth = DB::table('outflows as o')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('invoices as inv', 'ip.invoice_id', '=', 'inv.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->leftJoin('credit_debit_notes as cdn', 'cdni.credit_debit_note_id', '=', 'cdn.id')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->selectRaw('MONTH(COALESCE(inv.date, cdn.date, o.date)) as month_num, SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                    ELSE 0
                END) as total')
                ->groupByRaw('MONTH(COALESCE(inv.date, cdn.date, o.date))')
                ->pluck('total', 'month_num')
                ->toArray();

            $budgetData = [];
            $realData = [];
            $labels = [];

            foreach ($months as $month) {
                $monthId = $month['id'];
                $labels[] = $month['short_name'];
                $budgetData[] = (float) ($budgetedByMonth[$monthId] ?? 0);
                $realData[] = (float) ($realByMonth[$monthId] ?? 0);
            }

            return [
                'labels' => $labels,
                'budgeted' => $budgetData,
                'real' => $realData,
            ];
        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getMonthlyComparison: ' . $e->getMessage());
            return [
                'labels' => array_column($months, 'short_name'),
                'budgeted' => array_fill(0, 12, 0),
                'real' => array_fill(0, 12, 0),
            ];
        }
    }

    /**
     * Detalle por inversión individual
     */
    private function getInvestmentDetails($season_id, $team_id, $budgetedData)
    {
        try {
            $investments = Investment::where('season_id', $season_id)
                ->whereHas('season', fn($q) => $q->where('team_id', $team_id))
                ->with('costCenters:id,name')
                ->orderBy('name')
                ->get();

            $budgetedByInvestment = $budgetedData['totals'];

            // Obtener totales reales por investment_id
            $realByInvestment = DB::table('outflows as o')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->whereNotNull('o.investment_id')
                ->selectRaw('o.investment_id, SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                    ELSE 0
                END) as total')
                ->groupBy('o.investment_id')
                ->pluck('total', 'investment_id')
                ->toArray();

            // Real sin vincular a una inversión (operación Inversión pero investment_id NULL)
            $unassignedReal = (float) (DB::table('outflows as o')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->whereNull('o.investment_id')
                ->selectRaw('SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                    ELSE 0
                END) as total')
                ->value('total') ?? 0);

            $rows = $investments->map(function ($inv) use ($realByInvestment, $budgetedByInvestment) {
                $budgeted = (float) ($budgetedByInvestment[$inv->id] ?? 0);
                $real = (float) ($realByInvestment[$inv->id] ?? 0);
                $difference = $budgeted - $real;
                $execution = $budgeted > 0 ? ($real / $budgeted) * 100 : 0;

                return [
                    'id' => $inv->id,
                    'name' => $inv->name,
                    'estado' => $inv->estado,
                    'budgeted' => $budgeted,
                    'real' => $real,
                    'difference' => $difference,
                    'execution' => $execution,
                    'cost_centers' => $inv->costCenters->map(fn($cc) => $cc->name)->implode(', '),
                    'observations' => $inv->observations,
                    'unassigned' => false,
                ];
            })->values()->toArray();

            // Fila agregada: productos/salidas marcados como Inversión sin vincular a ninguna inversión puntual
            $unassignedBudgeted = (float) ($budgetedByInvestment[0] ?? 0);
            if ($unassignedBudgeted > 0 || $unassignedReal > 0) {
                $difference = $unassignedBudgeted - $unassignedReal;
                $execution = $unassignedBudgeted > 0 ? ($unassignedReal / $unassignedBudgeted) * 100 : 0;
                $rows[] = [
                    'id' => 0,
                    'name' => 'Inversión sin nombre asignado',
                    'estado' => null,
                    'budgeted' => $unassignedBudgeted,
                    'real' => $unassignedReal,
                    'difference' => $difference,
                    'execution' => $execution,
                    'cost_centers' => '',
                    'observations' => 'Productos o salidas clasificados como Inversión que aún no se han vinculado a una inversión específica.',
                    'unassigned' => true,
                ];
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getInvestmentDetails: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Desglose por centro de costo
     */
    private function getByCostCenter($season_id, $team_id)
    {
        try {
            // Presupuestado por CC (a través de la pivot cost_center_investment)
            $budgetedByCC = DB::table('cost_center_investment as cci')
                ->join('investments as i', 'cci.investment_id', '=', 'i.id')
                ->join('cost_centers as cc', 'cci.cost_center_id', '=', 'cc.id')
                ->where('i.season_id', $season_id)
                ->whereExists(function ($query) use ($team_id) {
                    $query->select(DB::raw(1))
                        ->from('seasons')
                        ->whereColumn('seasons.id', 'i.season_id')
                        ->where('seasons.team_id', $team_id);
                })
                ->selectRaw('cc.id as cost_center_id, cc.name as cost_center_name, SUM(i.amount) as total')
                ->groupBy('cc.id', 'cc.name')
                ->get()
                ->keyBy('cost_center_id');

            // Real por CC (a través de outflow_cost_centers)
            $realByCC = DB::table('outflows as o')
                ->join('outflow_cost_centers as occ', 'o.id', '=', 'occ.outflow_id')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->join('cost_centers as cc', 'occ.cost_center_id', '=', 'cc.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->selectRaw('cc.id as cost_center_id, cc.name as cost_center_name, SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN occ.percentage / 100 * o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN occ.percentage / 100 * o.quantity * cdni.unit_price
                    ELSE 0
                END) as total')
                ->groupBy('cc.id', 'cc.name')
                ->get()
                ->keyBy('cost_center_id');

            // Unir ambos conjuntos
            $allCCIds = $budgetedByCC->keys()->merge($realByCC->keys())->unique();

            return $allCCIds->map(function ($ccId) use ($budgetedByCC, $realByCC) {
                $budgeted = (float) ($budgetedByCC[$ccId]->total ?? 0);
                $real = (float) ($realByCC[$ccId]->total ?? 0);
                $name = $budgetedByCC[$ccId]->cost_center_name ?? $realByCC[$ccId]->cost_center_name ?? 'Sin CC';

                return [
                    'cost_center_id' => $ccId,
                    'cost_center_name' => $name,
                    'budgeted' => $budgeted,
                    'real' => $real,
                    'difference' => $budgeted - $real,
                    'execution' => $budgeted > 0 ? ($real / $budgeted) * 100 : 0,
                ];
            })->sortByDesc('budgeted')->values()->toArray();
        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getByCostCenter: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Salidas de tipo inversión agrupadas por Level3
     */
    private function getByLevel3($season_id, $team_id)
    {
        try {
            // Una sola query: investment → level3 → product
            $rows = DB::table('outflows as o')
                ->join('operations as op', 'o.operation_id', '=', 'op.id')
                ->leftJoin('investments as inv', 'o.investment_id', '=', 'inv.id')
                ->leftJoin('level3s as l3', 'o.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
                ->leftJoin('products as p', 'ip.product_id', '=', 'p.id')
                ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
                ->leftJoin('products as p2', 'cdni.product_id', '=', 'p2.id')
                ->where('o.season_id', $season_id)
                ->where('o.team_id', $team_id)
                ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
                ->selectRaw("
                    COALESCE(inv.id, 0)              as investment_id,
                    COALESCE(inv.name, 'Inversión sin nombre asignado') as investment_name,
                    COALESCE(l3.id, 0)               as level3_id,
                    COALESCE(l3.name, 'Sin clasificar') as level3_name,
                    COALESCE(l2.name, '')             as level2_name,
                    COALESCE(p.name, p2.name, 'Sin producto') as product_name,
                    SUM(CASE
                        WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                        WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                        ELSE 0
                    END) as total
                ")
                ->groupBy('inv.id', 'inv.name', 'l3.id', 'l3.name', 'l2.name', 'p.name', 'p2.name')
                ->orderBy('inv.name')
                ->orderByDesc('total')
                ->get();

            // Agrupar: investment → level3 → level2 → products
            $investments = [];
            foreach ($rows as $row) {
                $invId  = $row->investment_id;
                $l3Id   = $row->level3_id;
                $l2Name = $row->level2_name ?: 'Sin nivel 2';

                if (!isset($investments[$invId])) {
                    $investments[$invId] = [
                        'investment_id'   => $invId,
                        'investment_name' => $row->investment_name,
                        'total'           => 0,
                        'level3s'         => [],
                    ];
                }
                if (!isset($investments[$invId]['level3s'][$l3Id])) {
                    $investments[$invId]['level3s'][$l3Id] = [
                        'level3_id'   => $l3Id,
                        'level3_name' => $row->level3_name,
                        'total'       => 0,
                        'level2s'     => [],
                    ];
                }
                if (!isset($investments[$invId]['level3s'][$l3Id]['level2s'][$l2Name])) {
                    $investments[$invId]['level3s'][$l3Id]['level2s'][$l2Name] = [
                        'level2_name' => $l2Name,
                        'total'       => 0,
                        'products'    => [],
                    ];
                }

                $amount = (float) $row->total;
                $investments[$invId]['level3s'][$l3Id]['level2s'][$l2Name]['products'][] = [
                    'name'  => $row->product_name,
                    'total' => $amount,
                ];
                $investments[$invId]['level3s'][$l3Id]['level2s'][$l2Name]['total'] += $amount;
                $investments[$invId]['level3s'][$l3Id]['total'] += $amount;
                $investments[$invId]['total'] += $amount;
            }

            // Convertir a arrays indexados y ordenar
            return collect($investments)->map(function ($inv) {
                $inv['level3s'] = collect($inv['level3s'])->map(function ($l3) {
                    $l3['level2s'] = collect($l3['level2s'])
                        ->sortByDesc('total')
                        ->values()
                        ->toArray();
                    return $l3;
                })->sortByDesc('total')->values()->toArray();
                return $inv;
            })->sortByDesc('total')->values()->toArray();

        } catch (\Exception $e) {
            Log::error('InvestmentDashboard getByLevel3: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Total real de inversiones (outflows con operación inversión)
     */
    private function getRealInvestmentsTotal($season_id, $team_id)
    {
        return (float) (DB::table('outflows as o')
            ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
            ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
            ->join('operations as op', 'o.operation_id', '=', 'op.id')
            ->where('o.season_id', $season_id)
            ->where('o.team_id', $team_id)
            ->whereRaw('LOWER(op.name) LIKE ?', ['%inversion%'])
            ->selectRaw('SUM(CASE
                WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                ELSE 0
            END) as total')
            ->value('total') ?? 0);
    }
}
