<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Invoice;
use App\Models\CreditDebitNote;
use App\Models\CompanyReason;
use App\Models\Outflow;
use App\Models\CostCenter;
use App\Models\Agrochemical;
use App\Models\Fertilizer;
use App\Models\ManPower;
use App\Models\Service;
use App\Models\Supply;
use App\Models\Harvest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Controllers\Traits\BudgetTotalsTrait;
use App\Http\Controllers\Traits\PayrollDataTrait;
use App\Http\Controllers\Traits\OutflowProrationTrait;

class ComparativeOutflowsDashboardController extends Controller
{
    use BudgetTotalsTrait, PayrollDataTrait, OutflowProrationTrait;

    public function index(\Illuminate\Http\Request $request)
    {
        $season_id = session('season_id');
        $user = Auth::user();
        $team_id = $user->team_id;

        $company_reason_ids = collect($request->input('company_reason_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();
        $company_reason_id = count($company_reason_ids) > 0 ? $company_reason_ids : null;

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

        // Generar array de 12 meses desde el mes de inicio
        $months = $this->generateMonthsArray($startMonthId);

        // Calcular una sola vez y reutilizar (evita queries duplicadas)
        $monthlyComparison = $this->getMonthlyComparison($season_id, $team_id, $months, $company_reason_id);
        $comparisonByLevel1 = $this->getComparisonByLevel1($season_id, $team_id, $company_reason_id);
        $payrollByLevel2    = $this->getPayrollByLevel2($team_id, $season_id, $company_reason_id);
        $payrollByLevel3    = $this->getPayrollByLevel3($team_id, $season_id, $company_reason_id);

        // ── Merge payroll en las filas de detailedTable (match exacto por Nivel1+Nivel2+Nivel3) ──
        foreach ($payrollByLevel3 as $payrollRow) {
            $found = false;
            foreach ($comparisonByLevel1 as &$row) {
                if (
                    strcasecmp(trim($row['level1']), trim($payrollRow['level1'])) === 0 &&
                    strcasecmp(trim($row['level2']), trim($payrollRow['level2'])) === 0 &&
                    strcasecmp(trim($row['level3']), trim($payrollRow['level3'])) === 0
                ) {
                    $row['payroll']    = (float) $payrollRow['total'];
                    $row['difference'] = $row['budget'] - $row['invoiced'] - $row['payroll'];
                    $found = true;
                    break;
                }
            }
            unset($row);

            // Si no existe fila con ese Nivel3, crear una nueva (nómina sin presupuesto)
            if (!$found && $payrollRow['total'] > 0) {
                $comparisonByLevel1[] = [
                    'category'   => $payrollRow['level1'] . ' - ' . $payrollRow['level2'] . ' - ' . $payrollRow['level3'],
                    'level1'     => $payrollRow['level1'],
                    'level2'     => $payrollRow['level2'],
                    'level3'     => $payrollRow['level3'],
                    'budget'     => 0.0,
                    'invoiced'   => 0.0,
                    'consumed'   => 0.0,
                    'real'       => 0.0,
                    'payroll'    => (float) $payrollRow['total'],
                    'difference' => -(float) $payrollRow['total'],
                    'variance'   => 0.0,
                    'status'     => 'over',
                ];
            }
        }

        // Asegurar que todas las filas tengan el campo payroll
        foreach ($comparisonByLevel1 as &$row) {
            $row['payroll'] = $row['payroll'] ?? 0.0;
        }
        unset($row);

        return Inertia::render('ComparativeOutflowsDashboard', [
            'dollarPrice' => $dollarPrice,
            'isAdmin'     => $user->hasRole('Admin'),
            'companyReasons'        => $this->getCompanyReasons($season_id, $team_id),
            'activeCompanyReasonIds' => $company_reason_ids,
            'summary' => $this->getSummaryComparison($season_id, $team_id, $company_reason_id),
            'monthlyComparison' => $monthlyComparison,
            'cumulativeComparison' => $this->buildCumulativeFromMonthly($monthlyComparison, $months),
            'comparisonByLevel1' => $comparisonByLevel1,
            'comparisonByLevel2' => [],
            'detailedTable' => $comparisonByLevel1,
            'months' => $months,
            'seasonStartMonth' => $startMonthId,
            'payrollSummary'   => $this->getPayrollSummary($team_id, $season_id, $company_reason_id),
            'payrollMonthly'   => $this->getPayrollMonthly($team_id, $season_id, $months, $company_reason_id),
            'payrollByLevel2'  => $payrollByLevel2,
            'comparisonByLevel1Monthly' => $this->getComparisonByLevel1Monthly($season_id, $team_id, $company_reason_id, $months),
        ]);
    }

    /**
     * Genera array de meses desde el mes de inicio de la temporada
     */
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
     * Retorna las razones sociales disponibles (de facturas y centros de costo).
     */
    private function getCompanyReasons($season_id, $team_id): array
    {
        $fromInvoices = CompanyReason::whereIn(
            'id',
            Invoice::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereNotNull('company_reason_id')
                ->pluck('company_reason_id')
        )->get(['id', 'name']);

        $fromCostCenters = CompanyReason::whereIn(
            'id',
            CostCenter::where('season_id', $season_id)
                ->whereNotNull('company_reason_id')
                ->pluck('company_reason_id')
        )->get(['id', 'name']);

        return $fromInvoices->merge($fromCostCenters)
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn($cr) => ['value' => $cr->id, 'label' => $cr->name])
            ->toArray();
    }

    /**
     * Retorna IDs de centros de costo filtrados:
     * cuando hay filtro: company_reason_id = $id  OR  company_reason_id IS NULL
     * cuando no hay filtro: todos.
     */
    private function getFilteredCostCenterIds($season_id, $team_id, $company_reason_id)
    {
        return CostCenter::where('season_id', $season_id)
            ->whereHas('season.team', function ($q) use ($team_id) {
                $q->where('team_id', $team_id);
            })
            ->when($company_reason_id, function ($q) use ($company_reason_id) {
                $q->where(function ($w) use ($company_reason_id) {
                    $w->whereIn('company_reason_id', $company_reason_id)
                      ->orWhereNull('company_reason_id');
                });
            })
            ->pluck('id');
    }

    /**
     * Resumen comparativo general
     */
    private function getSummaryComparison($season_id, $team_id, $company_reason_id = null)
    {
        try {
            // Total Presupuestado - usa getBudgetTotalsByLevel12 para respetar el filtro de razón social
            // (CCs con company_reason_id = $filter OR IS NULL)
            $budgetTotal = (float) $this->getBudgetTotalsByLevel12($season_id, $team_id, $company_reason_id)->sum('total_amount');

            // Total Inversiones (no se filtran por razón social, son globales de la temporada)
            $monthsInvestmentsRaw = $this->getInvestmentsTotalByMonth($season_id, $team_id);
            $monthsInvestments = [];
            foreach($monthsInvestmentsRaw as $key => $value){
                $monthsInvestments[$key] = (float)$value;
            }
            // Normalizar a 12 meses
            $allMonthsInvestments = [];
            for ($i = 1; $i <= 12; $i++) {
                $key = (string)$i;
                $allMonthsInvestments[$key] = isset($monthsInvestments[$key]) ? $monthsInvestments[$key] : 0;
            }
            $totalInvestments = array_sum($allMonthsInvestments);
            
            // Total General = Total Neto + Inversiones
            $budgetTotalWithInvestments = $budgetTotal + $totalInvestments;

            // Helper closure: aplica filtro (company_reason_id = X OR IS NULL) a una query de invoices
            $applyInvoiceFilter = function ($q) use ($company_reason_id) {
                if (!$company_reason_id) return $q;
                return $q->where(function ($w) use ($company_reason_id) {
                    $w->whereIn('i.company_reason_id', $company_reason_id)
                      ->orWhereNull('i.company_reason_id');
                });
            };

            // Total Facturado - SQL aggregation con filtro de razón social
            $invoicesTotal = (float) ($applyInvoiceFilter(
                DB::table('invoices as i')
                    ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
                    ->where('i.team_id', $team_id)
                    ->where('i.season_id', $season_id)
            )->sum(DB::raw('ip.unit_price * ip.amount')) ?? 0);

            // Notas de crédito (se restan) — filtro por razón social de la factura asociada
            $creditNotesTotal = (float) (DB::table('credit_debit_notes as cdn')
                ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
                ->leftJoin('invoices as i', 'cdn.invoice_id', '=', 'i.id')
                ->where('cdn.team_id', $team_id)
                ->where('cdn.season_id', $season_id)
                ->where('cdn.affects_inventory', 1)
                ->whereRaw('LOWER(cdn.type) IN (?, ?)', ['credito', 'nc'])
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->whereIn('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->sum(DB::raw('cdni.unit_price * cdni.quantity')) ?? 0);

            // Notas de débito (se suman)
            $debitNotesTotal = (float) (DB::table('credit_debit_notes as cdn')
                ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
                ->leftJoin('invoices as i', 'cdn.invoice_id', '=', 'i.id')
                ->where('cdn.team_id', $team_id)
                ->where('cdn.season_id', $season_id)
                ->where('cdn.affects_inventory', 1)
                ->whereRaw('LOWER(cdn.type) NOT IN (?, ?)', ['credito', 'nc'])
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->whereIn('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->sum(DB::raw('cdni.unit_price * cdni.quantity')) ?? 0);

            $notesTotal = $debitNotesTotal - $creditNotesTotal;

            $invoicedTotal = floatval($invoicesTotal + $notesTotal);
            Log::info("getSummaryComparison - Budget: $budgetTotal, Facturado: $invoicedTotal (Invoices: $invoicesTotal + Notes: $notesTotal)");

            // ── Consumido: razón social por CENTRO DE COSTO + prorrateo por superficie ──
            // (mismo criterio que el Dashboard de Salidas; NO se filtra por la factura)
            $consumedOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with([
                    'invoiceProduct:id,unit_price',
                    'creditDebitNoteItem:id,unit_price',
                    'costCenters.costCenter:id,company_reason_id,surface',
                    'operation:id,name',
                ])
                ->get();

            $consumedTotalWithInvestments = 0.0;
            $consumedInvestmentsTotal = 0.0;
            foreach ($consumedOutflows as $outflow) {
                if (!$this->outflowMatchesCompanyReason($outflow, $company_reason_id)) continue;
                $amount = $this->proratedOutflowAmount($outflow, $company_reason_id);
                if ($amount == 0.0) continue;

                $consumedTotalWithInvestments += $amount;
                $isInvestment = $outflow->operation && stripos($outflow->operation->name, 'inversion') !== false;
                if ($isInvestment) {
                    $consumedInvestmentsTotal += $amount;
                }
            }

            // Consumido SIN inversiones = Total - Inversiones
            $consumedTotal = $consumedTotalWithInvestments - $consumedInvestmentsTotal;

            Log::info("getSummaryComparison - Consumido: $consumedTotal (con inv: $consumedTotalWithInvestments, inv: $consumedInvestmentsTotal)");

            // Usar facturado para cálculos principales
            $realTotal = $invoicedTotal;
            $difference = $budgetTotal - $realTotal;
            $percentageExecution = $budgetTotal > 0 ? ($realTotal / $budgetTotal) * 100 : 0;
            $variance = $budgetTotal > 0 ? (($realTotal - $budgetTotal) / $budgetTotal) * 100 : 0;

            // Obtener superficie total para cálculos por hectárea (filtrada por razón social)
            $totalSurface = DB::table('cost_centers')
                ->where('season_id', $season_id)
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->whereIn('company_reason_id', $company_reason_id)
                          ->orWhereNull('company_reason_id');
                    });
                })
                ->sum('surface');

            return [
                'budget_total' => floatval($budgetTotal), // Total Neto (sin inversiones)
                'budget_total_with_investments' => floatval($budgetTotalWithInvestments), // Total General (con inversiones)
                'total_investments' => floatval($totalInvestments),
                'real_total' => floatval($realTotal), // Facturado
                'invoiced_total' => floatval($invoicedTotal), // Facturado explícito
                'consumed_total' => floatval($consumedTotal), // Consumido sin inversiones
                'consumed_total_with_investments' => floatval($consumedTotalWithInvestments), // Consumido con inversiones
                'consumed_investments_total' => floatval($consumedInvestmentsTotal), // Solo inversiones consumidas
                'difference' => floatval($difference),
                'percentage_execution' => floatval($percentageExecution),
                'variance' => floatval($variance),
                'budget_per_hectare' => $totalSurface > 0 ? floatval($budgetTotal / $totalSurface) : 0,
                'budget_per_hectare_with_investments' => $totalSurface > 0 ? floatval($budgetTotalWithInvestments / $totalSurface) : 0,
                'real_per_hectare' => $totalSurface > 0 ? floatval($realTotal / $totalSurface) : 0,
                'invoiced_per_hectare' => $totalSurface > 0 ? floatval($invoicedTotal / $totalSurface) : 0,
                'consumed_per_hectare' => $totalSurface > 0 ? floatval($consumedTotal / $totalSurface) : 0,
                'consumed_per_hectare_with_investments' => $totalSurface > 0 ? floatval($consumedTotalWithInvestments / $totalSurface) : 0,
                'total_surface' => floatval($totalSurface),
                'status' => $variance > 0 ? 'over_budget' : 'under_budget',
            ];

        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getSummaryComparison: ' . $e->getMessage());
            return [
                'budget_total' => 0,
                'real_total' => 0,
                'invoiced_total' => 0,
                'consumed_total' => 0,
                'difference' => 0,
                'percentage_execution' => 0,
                'variance' => 0,
                'budget_per_hectare' => 0,
                'real_per_hectare' => 0,
                'invoiced_per_hectare' => 0,
                'consumed_per_hectare' => 0,
                'total_surface' => 0,
                'status' => 'under_budget',
            ];
        }
    }

    /**
     * Comparación mensual (no acumulada)
     * Usa EXACTAMENTE los mismos métodos que TechnicalPanelController
     */
    private function getMonthlyComparison($season_id, $team_id, $months, $company_reason_id = null)
    {
        try {
            // Usar la misma lógica que TechnicalPanelController
            $user = Auth::user();
            $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
            $month_id = $season ? $season->month_id : 1;
            
            // Obtener cost centers igual que TechnicalPanelController (filtrado por razón social)
            $costCenters = CostCenter::select('id', 'name')
                ->where('season_id', $season_id)
                ->whereHas('season.team', function($query) use ($team_id) {
                    $query->where('team_id', $team_id);
                })
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->whereIn('company_reason_id', $company_reason_id)
                          ->orWhereNull('company_reason_id');
                    });
                })
                ->get();
            $costCentersId = $costCenters->pluck('id');

            // Inicializar arrays para almacenar totales mensuales
            $monthsAgrochemical = [];
            $monthsFertilizer = [];
            $monthsManPower = [];
            $monthsServices = [];
            $monthsSupplies = [];
            $monthsHarvests = [];
            
            // Calcular usando los mismos métodos que TechnicalPanelController
            $this->getAgrochemicalProductsForComparison($costCentersId, $month_id, $monthsAgrochemical);
            $this->getFertilizerProductsForComparison($costCentersId, $month_id, $monthsFertilizer);
            $this->getManPowerProductsForComparison($costCentersId, $month_id, $monthsManPower);
            $this->getServicesProductsForComparison($costCentersId, $month_id, $monthsServices);
            $this->getSuppliesProductsForComparison($costCentersId, $month_id, $monthsSupplies);
            $this->getHarvestsProductsForComparison($costCentersId, $month_id, $monthsHarvests);

            // IMPORTANTE: Agregar Administración y Gral Campo (estaban faltando!)
            // Se prorratean por superficie según razón social activa
            $monthsAdministration = $this->getMonthsAdministration($team_id, $company_reason_id);
            $monthsFields = $this->getMonthsFields($team_id, $company_reason_id);

            // Obtener inversiones mensuales
            $monthsInvestmentsRaw = $this->getInvestmentsTotalByMonth($season_id, $team_id);
            $monthsInvestments = [];
            foreach($monthsInvestmentsRaw as $key => $value){
                $monthsInvestments[$key] = (float)$value;
            }

            // Crear arrays de presupuesto por mes
            $budgetByMonth = [];
            $budgetWithInvestmentsByMonth = [];
            $realByMonth = [];
            $consumedByMonth = [];
            $consumedWithInvestmentsByMonth = [];

            // Batch: obtener facturado y consumido de TODOS los meses en pocas queries
            $allInvoicedByMonth = $this->getAllInvoicedByMonth($season_id, $team_id, $company_reason_id);
            $allConsumedByMonth = $this->getAllConsumedByMonth($season_id, $team_id, $company_reason_id);

            foreach ($months as $month) {
                $monthId = $month['id'];
                
                // Presupuesto: sumar TODAS las categorías para este mes (incluyendo Admin y Fields)
                $budgetMonth = 
                    ($monthsAdministration[$monthId] ?? 0) +
                    ($monthsFields[$monthId] ?? 0) +
                    ($monthsAgrochemical[$monthId] ?? 0) +
                    ($monthsFertilizer[$monthId] ?? 0) +
                    ($monthsManPower[$monthId] ?? 0) +
                    ($monthsServices[$monthId] ?? 0) +
                    ($monthsSupplies[$monthId] ?? 0) +
                    ($monthsHarvests[$monthId] ?? 0);
                
                $budgetByMonth[] = floatval($budgetMonth);

                // Presupuesto con inversiones
                $investmentMonth = $monthsInvestments[$monthId] ?? 0;
                $budgetWithInvestmentsByMonth[] = floatval($budgetMonth + $investmentMonth);

                // Facturado del mes (pre-calculado en batch)
                $realByMonth[] = floatval($allInvoicedByMonth[$monthId] ?? 0);

                // Consumido del mes (pre-calculado en batch)
                $consumedByMonth[] = floatval($allConsumedByMonth[$monthId]['total'] ?? 0);
                $consumedWithInvestmentsByMonth[] = floatval($allConsumedByMonth[$monthId]['total_with_investments'] ?? 0);
            }

            Log::info('Monthly Comparison - Budget: ' . array_sum($budgetByMonth) . ', BudgetInv: ' . array_sum($budgetWithInvestmentsByMonth) . ', Facturado: ' . array_sum($realByMonth));

            return [
                'labels' => array_column($months, 'short_name'),
                'budget' => $budgetByMonth,
                'budget_with_investments' => $budgetWithInvestmentsByMonth,
                'real' => $realByMonth,
                'consumed' => $consumedByMonth,
                'consumed_with_investments' => $consumedWithInvestmentsByMonth,
            ];

        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getMonthlyComparison: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return [
                'labels' => array_column($months, 'short_name'),
                'budget' => array_fill(0, 12, 0),
                'real' => array_fill(0, 12, 0),
            ];
        }
    }

    /**
     * Comparación acumulada mes a mes
     */
    private function buildCumulativeFromMonthly($monthlyData, $months)
    {
        try {
            $budgetCumulative = [];
            $budgetWithInvestmentsCumulative = [];
            $realCumulative = [];
            $consumedCumulative = [];
            $consumedWithInvestmentsCumulative = [];
            $accumulatedBudget = 0;
            $accumulatedBudgetWithInvestments = 0;
            $accumulatedReal = 0;
            $accumulatedConsumed = 0;
            $accumulatedConsumedWithInvestments = 0;
            
            // Detectar último mes con datos reales (facturado > 0)
            $lastMonthWithData = 0;
            foreach ($monthlyData['real'] as $index => $realValue) {
                if ($realValue > 0) {
                    $lastMonthWithData = $index;
                }
            }

            // Detectar último mes con datos de consumo
            $lastMonthWithConsumedData = 0;
            foreach ($monthlyData['consumed'] as $index => $consumedValue) {
                if ($consumedValue > 0) {
                    $lastMonthWithConsumedData = $index;
                }
            }

            foreach ($monthlyData['budget'] as $index => $budgetValue) {
                $accumulatedBudget += $budgetValue;
                $budgetCumulative[] = floatval($accumulatedBudget);

                // Acumular presupuesto con inversiones
                $budgetWithInvestmentsValue = $monthlyData['budget_with_investments'][$index];
                $accumulatedBudgetWithInvestments += $budgetWithInvestmentsValue;
                $budgetWithInvestmentsCumulative[] = floatval($accumulatedBudgetWithInvestments);
                
                // Solo acumular real hasta el último mes con datos
                if ($index <= $lastMonthWithData) {
                    $accumulatedReal += $monthlyData['real'][$index];
                    $realCumulative[] = floatval($accumulatedReal);
                } else {
                    $realCumulative[] = null; // null para no mostrar línea después
                }

                // Solo acumular consumido hasta el último mes con datos
                if ($index <= $lastMonthWithConsumedData) {
                    $accumulatedConsumed += $monthlyData['consumed'][$index];
                    $accumulatedConsumedWithInvestments += $monthlyData['consumed_with_investments'][$index];
                    $consumedCumulative[] = floatval($accumulatedConsumed);
                    $consumedWithInvestmentsCumulative[] = floatval($accumulatedConsumedWithInvestments);
                } else {
                    $consumedCumulative[] = null;
                    $consumedWithInvestmentsCumulative[] = null;
                }
            }

            Log::info('Cumulative Comparison - Last month with data: ' . $lastMonthWithData . ', consumed: ' . $lastMonthWithConsumedData);

            return [
                'labels' => $monthlyData['labels'],
                'budget_cumulative' => $budgetCumulative,
                'budget_with_investments_cumulative' => $budgetWithInvestmentsCumulative,
                'real_cumulative' => $realCumulative,
                'consumed_cumulative' => $consumedCumulative,
                'consumed_with_investments_cumulative' => $consumedWithInvestmentsCumulative,
                'last_month_with_data' => $lastMonthWithData,
                'last_month_with_consumed_data' => $lastMonthWithConsumedData,
            ];

        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard buildCumulativeFromMonthly: ' . $e->getMessage());
            return [
                'labels' => array_column($months, 'short_name'),
                'budget_cumulative' => array_fill(0, 12, 0),
                'budget_with_investments_cumulative' => array_fill(0, 12, 0),
                'real_cumulative' => array_fill(0, 12, 0),
                'consumed_cumulative' => array_fill(0, 12, 0),
                'consumed_with_investments_cumulative' => array_fill(0, 12, 0),
            ];
        }
    }

    /**
     * Obtener presupuesto de un mes específico
     * Metodología TechnicalPanelController: 
     * 1. Agrupar items por (producto_id, cost_center_id, month_id)
     * 2. Si count > 0, calcular monto = precio × dosis × superficie UNA VEZ por cost_center
     */
    private function getBudgetForMonth($season_id, $team_id, $month_id)
    {
        $total = 0;

        try {
            // Obtener cost centers del equipo
            $costCentersId = CostCenter::where('season_id', $season_id)
                ->whereHas('season.team', function($query) use ($team_id) {
                    $query->where('team_id', $team_id);
                })
                ->pluck('id');

            if ($costCentersId->isEmpty()) {
                return 0;
            }

            // 1. AGROQUÍMICOS
            // Obtener items agrupados y contar
            $agroItems = DB::table('agrochemical_items')
                ->select('agrochemical_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('agrochemical_id', 'cost_center_id')
                ->get();

            if ($agroItems->isNotEmpty()) {
                $agrochemicalIds = $agroItems->pluck('agrochemical_id')->unique();
                
                $agrochemicals = Agrochemical::whereIn('id', $agrochemicalIds)
                    ->select('id', 'price', 'dose_type_id', 'dose', 'unit_id', 'unit_id_price', 'mojamiento')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($agroItems as $item) {
                    $agro = $agrochemicals->get($item->agrochemical_id);
                    if (!$agro) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    
                    $dose = (($agro->unit_id == 4 && $agro->unit_id_price == 3) || 
                            ($agro->unit_id == 2 && $agro->unit_id_price == 1)) 
                            ? ($agro->dose / 1000) 
                            : $agro->dose;
                    
                    if ($agro->dose_type_id == 1) {
                        $quantity = round($dose * $surface, 2);
                    } elseif ($agro->dose_type_id == 2) {
                        $quantity = round((($agro->mojamiento / 100) * $dose * $surface), 2);
                    } else {
                        $quantity = 0;
                    }
                    
                    $amount = round($agro->price * $quantity, 2);
                    $total += $amount;
                }
            }

            // 2. FERTILIZANTES
            $fertItems = DB::table('fertilizer_items')
                ->select('fertilizer_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('fertilizer_id', 'cost_center_id')
                ->get();

            if ($fertItems->isNotEmpty()) {
                $fertilizerIds = $fertItems->pluck('fertilizer_id')->unique();
                
                $fertilizers = Fertilizer::whereIn('id', $fertilizerIds)
                    ->select('id', 'price', 'dose', 'unit_id', 'unit_id_price')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($fertItems as $item) {
                    $fert = $fertilizers->get($item->fertilizer_id);
                    if (!$fert) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    
                    $dose = (($fert->unit_id == 4 && $fert->unit_id_price == 3) || 
                            ($fert->unit_id == 2 && $fert->unit_id_price == 1)) 
                            ? ($fert->dose / 1000) 
                            : $fert->dose;
                    
                    $quantity = round($dose * $surface, 2);
                    $amount = round($fert->price * $quantity, 2);
                    $total += $amount;
                }
            }

            // 3. MANO DE OBRA
            $mpItems = DB::table('manpower_items')
                ->select('man_power_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('man_power_id', 'cost_center_id')
                ->get();

            if ($mpItems->isNotEmpty()) {
                $manPowerIds = $mpItems->pluck('man_power_id')->unique();
                
                $manpowers = ManPower::whereIn('id', $manPowerIds)
                    ->select('id', 'price', 'workday')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($mpItems as $item) {
                    $mp = $manpowers->get($item->man_power_id);
                    if (!$mp) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    $quantity = round($mp->workday * $surface, 2);
                    $amount = round($mp->price * $quantity, 2);
                    $total += $amount;
                }
            }

            // 4. SERVICIOS
            $servItems = DB::table('service_items')
                ->select('service_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('service_id', 'cost_center_id')
                ->get();

            if ($servItems->isNotEmpty()) {
                $serviceIds = $servItems->pluck('service_id')->unique();
                
                $services = Service::whereIn('id', $serviceIds)
                    ->select('id', 'price', 'quantity', 'unit_id', 'unit_id_price')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($servItems as $item) {
                    $serv = $services->get($item->service_id);
                    if (!$serv) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    
                    $quantity = (($serv->unit_id == 4 && $serv->unit_id_price == 3) || 
                                ($serv->unit_id == 2 && $serv->unit_id_price == 1)) 
                                ? ($serv->quantity / 1000) 
                                : $serv->quantity;
                    
                    $quantityCalc = round($quantity * $surface, 2);
                    $amount = round($serv->price * $quantityCalc, 2);
                    $total += $amount;
                }
            }

            // 5. INSUMOS
            $supItems = DB::table('supply_items')
                ->select('supply_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('supply_id', 'cost_center_id')
                ->get();

            if ($supItems->isNotEmpty()) {
                $supplyIds = $supItems->pluck('supply_id')->unique();
                
                $supplies = Supply::whereIn('id', $supplyIds)
                    ->select('id', 'price', 'quantity', 'unit_id', 'unit_id_price')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($supItems as $item) {
                    $sup = $supplies->get($item->supply_id);
                    if (!$sup) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    
                    $quantity = (($sup->unit_id == 4 && $sup->unit_id_price == 3) || 
                                ($sup->unit_id == 2 && $sup->unit_id_price == 1)) 
                                ? ($sup->quantity / 1000) 
                                : $sup->quantity;
                    
                    $quantityCalc = round($quantity * $surface, 2);
                    $amount = round($sup->price * $quantityCalc, 2);
                    $total += $amount;
                }
            }

            // 6. COSECHA
            $harvItems = DB::table('harvest_items')
                ->select('harvest_id', 'cost_center_id', DB::raw('COUNT(*) as count'))
                ->whereIn('cost_center_id', $costCentersId)
                ->where('month_id', $month_id)
                ->groupBy('harvest_id', 'cost_center_id')
                ->get();

            if ($harvItems->isNotEmpty()) {
                $harvestIds = $harvItems->pluck('harvest_id')->unique();
                
                $harvests = Harvest::whereIn('id', $harvestIds)
                    ->select('id', 'price', 'quantity', 'unit_id', 'unit_id_price')
                    ->get()
                    ->keyBy('id');

                $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

                foreach ($harvItems as $item) {
                    $harv = $harvests->get($item->harvest_id);
                    if (!$harv) continue;

                    $surface = $surfaces[$item->cost_center_id] ?? 0;
                    
                    $quantity = (($harv->unit_id == 4 && $harv->unit_id_price == 3) || 
                                ($harv->unit_id == 2 && $harv->unit_id_price == 1)) 
                                ? ($harv->quantity / 1000) 
                                : $harv->quantity;
                    
                    $quantityCalc = round($quantity * $surface, 2);
                    $amount = round($harv->price * $quantityCalc, 2);
                    $total += $amount;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error en getBudgetForMonth (month_id: ' . $month_id . '): ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }

        return floatval($total);
    }

    /**
     * Obtener total facturado de TODOS los meses en 2 queries (invoices + notas).
     * Reemplaza 12× getInvoicedForMonth() → 24 queries por 2 queries.
     * @return array [month_id => total]
     */
    private function getAllInvoicedByMonth($season_id, $team_id, $company_reason_id = null)
    {
        // Inicializar todos los meses en 0
        $result = array_fill(1, 12, 0.0);

        try {
            // Query 1: Todas las facturas agrupadas por mes (filtro razón social)
            $invoicesByMonth = DB::table('invoices as i')
                ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
                ->where('i.team_id', $team_id)
                ->where('i.season_id', $season_id)
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->where('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->select(
                    DB::raw('MONTH(i.date) as month_id'),
                    DB::raw('SUM(ip.unit_price * ip.amount) as total')
                )
                ->groupBy(DB::raw('MONTH(i.date)'))
                ->pluck('total', 'month_id');

            foreach ($invoicesByMonth as $monthId => $total) {
                $result[$monthId] = floatval($total);
            }

            // Query 2: Notas agrupadas por mes y tipo (filtro razón social vía invoice)
            // Las NC financieras ya ajustaron el unit_price del invoice_product
            $notesByMonth = DB::table('credit_debit_notes as cdn')
                ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
                ->leftJoin('invoices as i', 'cdn.invoice_id', '=', 'i.id')
                ->where('cdn.team_id', $team_id)
                ->where('cdn.season_id', $season_id)
                ->where('cdn.affects_inventory', 1)
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->where('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->select(
                    DB::raw('MONTH(cdn.date) as month_id'),
                    'cdn.type',
                    DB::raw('SUM(cdni.unit_price * cdni.quantity) as total')
                )
                ->groupBy(DB::raw('MONTH(cdn.date)'), 'cdn.type')
                ->get();

            foreach ($notesByMonth as $note) {
                $monto = floatval($note->total);
                $type = strtolower($note->type);
                if ($type === 'credito' || $type === 'nc') {
                    $result[$note->month_id] -= $monto;
                } else {
                    $result[$note->month_id] += $monto;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en getAllInvoicedByMonth: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Obtener consumido de TODOS los meses en 1 carga (outflows + eager loading).
     * Reemplaza 12× getConsumedForMonth() → 24-48 queries por ~4 queries.
     * @return array [month_id => ['total' => float, 'total_with_investments' => float]]
     */
    private function getAllConsumedByMonth($season_id, $team_id, $company_reason_id = null)
    {
        // Inicializar todos los meses
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $result[$m] = ['total' => 0.0, 'total_with_investments' => 0.0];
        }

        try {
            // Cargar TODOS los outflows de la temporada una sola vez.
            // Razón social por CENTRO DE COSTO + prorrateo por superficie (mismo
            // criterio que el Dashboard de Salidas; el filtro es por CC, no por factura).
            $allOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with([
                    'invoiceProduct:id,unit_price,invoice_id',
                    'invoiceProduct.invoice:id,date',
                    'creditDebitNoteItem:id,unit_price,credit_debit_note_id',
                    'creditDebitNoteItem.creditDebitNote:id,date',
                    'costCenters.costCenter:id,company_reason_id,surface',
                    'operation:id,name',
                ])
                ->get();

            foreach ($allOutflows as $outflow) {
                if (!$this->outflowMatchesCompanyReason($outflow, $company_reason_id)) continue;

                $monthId = null;
                // Determinar mes desde la factura o nota
                if ($outflow->invoice_product_id && $outflow->invoiceProduct && $outflow->invoiceProduct->invoice) {
                    $monthId = (int) date('n', strtotime($outflow->invoiceProduct->invoice->date));
                } elseif ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem && $outflow->creditDebitNoteItem->creditDebitNote) {
                    $monthId = (int) date('n', strtotime($outflow->creditDebitNoteItem->creditDebitNote->date));
                }
                if (!$monthId) continue;

                $amount = $this->proratedOutflowAmount($outflow, $company_reason_id);
                if ($amount == 0.0) continue;

                // Verificar si es inversión
                $isInvestment = $outflow->operation && stripos($outflow->operation->name, 'inversion') !== false;

                $result[$monthId]['total_with_investments'] += $amount;
                if (!$isInvestment) {
                    $result[$monthId]['total'] += $amount;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en getAllConsumedByMonth: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Obtener total facturado de un mes específico desde invoices
     * Usa la fecha de la factura (date) para determinar el mes
     * Incluye notas de crédito/débito (restando créditos, sumando débitos)
     */
    private function getInvoicedForMonth($season_id, $team_id, $month_id)
    {
        try {
            // Total de facturas del mes
            $invoicesTotal = Invoice::where('team_id', $team_id)
                ->where('season_id', $season_id)
                ->whereRaw('MONTH(date) = ?', [$month_id])
                ->with(['invoiceProducts'])
                ->get()
                ->sum(function($invoice) {
                    return $invoice->invoiceProducts->sum(function($ip) {
                        return $ip->unit_price * $ip->amount;
                    });
                });

            // Total de notas de crédito/débito del mes (solo affects_inventory=1)
            $notesTotal = CreditDebitNote::where('team_id', $team_id)
                ->where('season_id', $season_id)
                ->where('affects_inventory', 1)
                ->whereRaw('MONTH(date) = ?', [$month_id])
                ->with(['items'])
                ->get()
                ->sum(function($note) {
                    $monto = $note->items->sum(function($item) {
                        return $item->unit_price * $item->quantity;
                    });
                    
                    // Si es nota de crédito (credito, Crédito, NC), restar
                    $type = strtolower($note->type);
                    if ($type === 'credito' || $type === 'nc') {
                        return -$monto;
                    }
                    // Si es nota de débito (debito, Débito, ND), sumar
                    return $monto;
                });

            return floatval($invoicesTotal + $notesTotal);

        } catch (\Exception $e) {
            Log::error('Error en getInvoicedForMonth (month_id: ' . $month_id . '): ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener consumido de un mes específico desde outflows
     * Retorna dos valores: total (sin inversiones) y total_with_investments (con inversiones)
     * IMPORTANTE: Filtra por el MES DE LA FACTURA/NOTA, no por la fecha del outflow
     * 
     * @param int $season_id
     * @param int $team_id
     * @param int $month_id Número del mes (1-12)
     * @return array ['total' => float, 'total_with_investments' => float]
     */
    private function getConsumedForMonth($season_id, $team_id, $month_id)
    {
        try {
            // Consumido CON inversiones (todos los outflows del mes según fecha de factura)
            $allOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where(function($query) use ($month_id) {
                    // Filtrar por mes de factura (invoice_product)
                    $query->whereHas('invoiceProduct.invoice', function($q) use ($month_id) {
                        $q->whereRaw('MONTH(date) = ?', [$month_id]);
                    })
                    // O por mes de nota de crédito/débito
                    ->orWhereHas('creditDebitNoteItem.creditDebitNote', function($q) use ($month_id) {
                        $q->whereRaw('MONTH(date) = ?', [$month_id]);
                    });
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $totalWithInvestments = $allOutflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            // Consumido SOLO inversiones (también por mes de factura)
            $investmentOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where(function($query) use ($month_id) {
                    // Filtrar por mes de factura (invoice_product)
                    $query->whereHas('invoiceProduct.invoice', function($q) use ($month_id) {
                        $q->whereRaw('MONTH(date) = ?', [$month_id]);
                    })
                    // O por mes de nota de crédito/débito
                    ->orWhereHas('creditDebitNoteItem.creditDebitNote', function($q) use ($month_id) {
                        $q->whereRaw('MONTH(date) = ?', [$month_id]);
                    });
                })
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $investmentsTotal = $investmentOutflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            // Total sin inversiones
            $total = $totalWithInvestments - $investmentsTotal;

            return [
                'total' => floatval($total),
                'total_with_investments' => floatval($totalWithInvestments),
                'investments' => floatval($investmentsTotal)
            ];

        } catch (\Exception $e) {
            Log::error('Error en getConsumedForMonth (month_id: ' . $month_id . '): ' . $e->getMessage());
            return [
                'total' => 0,
                'total_with_investments' => 0,
                'investments' => 0
            ];
        }
    }

    /**
     * Obtener real de un mes específico desde outflows
     * Considera que la temporada puede cruzar años (ej: Mayo 2025 a Abril 2026)
     */
    /**
     * Obtener real de un mes específico desde outflows
     * 
     * IMPORTANTE: Este método filtra outflows por:
     * 1. season_id y team_id (contexto del usuario)
     * 2. Excluye inversiones (para comparar manzanas con manzanas vs presupuesto)
     * 3. Filtra por mes usando MONTH(date)
     * 
     * NOTA: Si la temporada cruza años (ej: Mayo 2025 a Abril 2026), 
     * este método suma TODOS los outflows de ese mes sin importar el año.
     * Esto es intencional porque los outflows ya están filtrados por season_id,
     * lo que garantiza que solo se consideren datos de esta temporada específica.
     * 
     * @param int $season_id
     * @param int $team_id
     * @param int $month_id Número del mes (1-12)
     * @return float Total de outflows para ese mes
     */
    private function getRealForMonth($season_id, $team_id, $month_id)
    {
        try {
            // Obtener outflows con relaciones necesarias
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where(function($query) {
                    $query->whereDoesntHave('operation')
                        ->orWhereDoesntHave('operation', function($q) {
                            $q->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                        });
                })
                ->with([
                    'invoiceProduct.invoice:id,month_id',
                    'invoiceProduct:id,invoice_id,unit_price',
                    'creditDebitNoteItem.creditDebitNote:id,month_id',
                    'creditDebitNoteItem:id,credit_debit_note_id,unit_price'
                ])
                ->get();

            // Filtrar por mes contable y calcular total
            // Total = quantity * unit_price (misma lógica que columna "Total" en vista Outflows)
            $total = $outflows->sum(function($outflow) use ($month_id) {
                // Caso 1: Outflow desde invoice_product
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    $invoice = $outflow->invoiceProduct->invoice;
                    if ($invoice && $invoice->month_id == $month_id) {
                        return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                    }
                }
                
                // Caso 2: Outflow desde credit_debit_note_item
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    $note = $outflow->creditDebitNoteItem->creditDebitNote;
                    if ($note && $note->month_id == $month_id) {
                        return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                    }
                }
                
                return 0;
            });

            return floatval($total);

        } catch (\Exception $e) {
            Log::error('Error en getRealForMonth (month_id: ' . $month_id . '): ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * Comparación por Level1
     */
    private function getComparisonByLevel1($season_id, $team_id, $company_reason_id = null)
    {
        try {
            // Inicializar array para almacenar todas las categorías encontradas
            $categories = [];

            // ========================================
            // 1. PRESUPUESTO por categoría - USANDO RELACIONES CON LEVEL1/LEVEL2
            // ========================================
            $budgetByLevel = $this->getBudgetTotalsByLevel12($season_id, $team_id, $company_reason_id);
            
            foreach ($budgetByLevel as $row) {
                $fullName = $row['level1_name'] . ' - ' . $row['level2_name'] . ' - ' . $row['level3_name'];
                
                $categories[$fullName] = [
                    'budget' => floatval($row['total_amount']),
                    'invoiced' => 0,
                    'consumed' => 0
                ];
            }

            // ========================================
            // 2. FACTURADO por categoría (Level2 real de la BD)
            // ========================================
            
            // Facturas (filtro razón social)
            $invoicesByLevel2 = DB::table('invoices as i')
                ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
                ->join('products as p', 'ip.product_id', '=', 'p.id')
                ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('i.team_id', $team_id)
                ->where('i.season_id', $season_id)
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->where('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->select(
                    DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                    DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                    DB::raw('COALESCE(l3.name, "Sin Clasificar") as level3_name'),
                    DB::raw('SUM(ip.unit_price * ip.amount) as total')
                )
                ->groupBy('level1_name', 'level2_name', 'level3_name')
                ->get();

            foreach ($invoicesByLevel2 as $row) {
                $fullName = $row->level1_name . ' - ' . $row->level2_name . ' - ' . $row->level3_name;
                
                if (!isset($categories[$fullName])) {
                    $categories[$fullName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                }
                
                $categories[$fullName]['invoiced'] += floatval($row->total);
            }

            // Notas de Crédito/Débito (solo affects_inventory=1) con filtro razón social
            $notesByLevel2 = DB::table('credit_debit_notes as cdn')
                ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
                ->join('products as p', 'cdni.product_id', '=', 'p.id')
                ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->leftJoin('invoices as i', 'cdn.invoice_id', '=', 'i.id')
                ->where('cdn.team_id', $team_id)
                ->where('cdn.season_id', $season_id)
                ->where('cdn.affects_inventory', 1)
                ->when($company_reason_id, function ($q) use ($company_reason_id) {
                    $q->where(function ($w) use ($company_reason_id) {
                        $w->where('i.company_reason_id', $company_reason_id)
                          ->orWhereNull('i.company_reason_id');
                    });
                })
                ->select(
                    DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                    DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                    DB::raw('COALESCE(l3.name, "Sin Clasificar") as level3_name'),
                    'cdn.type',
                    DB::raw('SUM(cdni.unit_price * cdni.quantity) as total')
                )
                ->groupBy('level1_name', 'level2_name', 'level3_name', 'cdn.type')
                ->get();

            foreach ($notesByLevel2 as $row) {
                $fullName = $row->level1_name . ' - ' . $row->level2_name . ' - ' . $row->level3_name;
                
                if (!isset($categories[$fullName])) {
                    $categories[$fullName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                }
                
                $monto = floatval($row->total);
                $type = strtolower($row->type);
                
                if ($type === 'credito' || $type === 'nc') {
                    $categories[$fullName]['invoiced'] -= $monto;
                } else {
                    $categories[$fullName]['invoiced'] += $monto;
                }
            }

            // ========================================
            // 3. CONSUMIDO por categoría (Level2 real de la BD)
            //    Razón social por CENTRO DE COSTO + prorrateo por superficie
            //    (mismo criterio que el Dashboard de Salidas)
            // ========================================
            
            $outflowsByLevel2 = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereDoesntHave('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with([
                    'level3.level2.level1',
                    'invoiceProduct:id,unit_price',
                    'creditDebitNoteItem:id,unit_price',
                    'costCenters.costCenter:id,company_reason_id,surface',
                ])
                ->get()
                ->groupBy(function($outflow) {
                    if ($outflow->level3 && $outflow->level3->level2) {
                        $level1Name = $outflow->level3->level2->level1 ? $outflow->level3->level2->level1->name : 'Sin Clasificar';
                        $level2Name = $outflow->level3->level2->name;
                        $level3Name = $outflow->level3->name ?? 'Sin Clasificar';
                        return $level1Name . ' - ' . $level2Name . ' - ' . $level3Name;
                    }
                    return 'Sin Clasificar - Sin Clasificar - Sin Clasificar';
                });

            foreach ($outflowsByLevel2 as $fullName => $outflows) {
                $total = $outflows->sum(function($outflow) use ($company_reason_id) {
                    if (!$this->outflowMatchesCompanyReason($outflow, $company_reason_id)) return 0.0;
                    return $this->proratedOutflowAmount($outflow, $company_reason_id);
                });

                if (!isset($categories[$fullName])) {
                    $categories[$fullName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                }
                
                $categories[$fullName]['consumed'] += floatval($total);
            }

            Log::info('Categorías finales:', ['count' => count($categories)]);

            // Calcular variaciones (usando facturado como "real")
            $result = [];
            foreach ($categories as $name => $data) {
                // Extraer level1, level2 y level3 del nombre
                $parts = explode(' - ', $name, 3);
                $level1Name = $parts[0] ?? 'Sin Clasificar';
                $level2Name = $parts[1] ?? 'Sin Clasificar';
                $level3Name = $parts[2] ?? 'Sin Clasificar';
                
                $variance = $data['budget'] > 0 
                    ? (($data['invoiced'] - $data['budget']) / $data['budget']) * 100 
                    : 0;
                
                $result[] = [
                    'category' => $name,
                    'level1' => $level1Name,
                    'level2' => $level2Name,
                    'level3' => $level3Name,
                    'budget' => $data['budget'],
                    'invoiced' => $data['invoiced'],
                    'consumed' => $data['consumed'],
                    'real' => $data['invoiced'], // Para compatibilidad con código existente
                    'difference' => $data['budget'] - $data['invoiced'],
                    'variance' => $variance,
                    'status' => $variance > 0 ? 'over' : 'under',
                ];
            }

            // Ordenar: primero por Level1, luego por Level2
            usort($result, function($a, $b) {
                // Normalizar level1 para comparación (sin tildes, minúsculas)
                $normalize = function($str) {
                    $str = strtolower(trim($str));
                    $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $str);
                    return $str;
                };
                
                $level1ANormalized = $normalize($a['level1']);
                $level1BNormalized = $normalize($b['level1']);
                
                // Definir orden de Level1 (normalizado)
                $level1Order = [
                    'costos directos' => 1,
                    'administracion' => 2,
                    'generales campo' => 3,
                    'cosecha' => 4,
                    'sin clasificar' => 5
                ];
                
                $orderA = $level1Order[$level1ANormalized] ?? 99;
                $orderB = $level1Order[$level1BNormalized] ?? 99;
                
                if ($orderA !== $orderB) {
                    return $orderA - $orderB;
                }
                
                // Si el level1 es igual, ordenar por level2 y luego level3 alfabéticamente
                $cmp = strcmp(strtolower($a['level2']), strtolower($b['level2']));
                if ($cmp !== 0) return $cmp;
                return strcmp(strtolower($a['level3'] ?? ''), strtolower($b['level3'] ?? ''));
            });

            return $result;

        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getComparisonByLevel1: ' . $e->getMessage());
            return [];
        }
    }

    // getComparisonByLevel2 y getDetailedComparisonTable eliminados: se calculan una sola vez en index()

    /**
     * Facturado (facturas + notas de crédito/débito) agrupado por Nivel1/2/3 y por mes de temporada.
     * Usa i.month_id / cdn.month_id (no requiere parsear fechas) y los indexa según la posición
     * de cada mes en el array $months generado por generateMonthsArray().
     *
     * @return array ["level1||level2||level3" => ['level1'=>, 'level2'=>, 'level3'=>, 'monthly'=> float[12]]]
     */
    private function getInvoicedMonthlyByLevel123($season_id, $team_id, $company_reason_id, array $months): array
    {
        $monthIndexMap = [];
        foreach ($months as $i => $m) {
            $monthIndexMap[(int) $m['id']] = $i;
        }

        $map = [];
        $addAmount = function ($level1, $level2, $level3, $monthId, $amount) use (&$map, $monthIndexMap) {
            $idx = $monthIndexMap[(int) $monthId] ?? null;
            if ($idx === null) return;
            $level1 = $level1 ?: 'Sin Clasificar';
            $level2 = $level2 ?: 'Sin Clasificar';
            $level3 = $level3 ?: 'Sin Clasificar';
            $key = $level1 . '||' . $level2 . '||' . $level3;
            if (!isset($map[$key])) {
                $map[$key] = ['level1' => $level1, 'level2' => $level2, 'level3' => $level3, 'monthly' => array_fill(0, 12, 0.0)];
            }
            $map[$key]['monthly'][$idx] += (float) $amount;
        };

        // Facturas
        $invoicesByMonth = DB::table('invoices as i')
            ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
            ->join('products as p', 'ip.product_id', '=', 'p.id')
            ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('i.team_id', $team_id)
            ->where('i.season_id', $season_id)
            ->when($company_reason_id, function ($q) use ($company_reason_id) {
                $q->where(function ($w) use ($company_reason_id) {
                    $w->whereIn('i.company_reason_id', $company_reason_id)
                      ->orWhereNull('i.company_reason_id');
                });
            })
            ->select(
                DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                DB::raw('COALESCE(l3.name, "Sin Clasificar") as level3_name'),
                'i.month_id',
                DB::raw('SUM(ip.unit_price * ip.amount) as total')
            )
            ->groupBy('level1_name', 'level2_name', 'level3_name', 'i.month_id')
            ->get();

        foreach ($invoicesByMonth as $row) {
            $addAmount($row->level1_name, $row->level2_name, $row->level3_name, $row->month_id, $row->total);
        }

        // Notas de crédito/débito (solo affects_inventory=1)
        // Se agrupa por MONTH(cdn.date) y no por cdn.month_id porque este último
        // nunca se guarda al crear/editar la nota (queda siempre NULL).
        $notesByMonth = DB::table('credit_debit_notes as cdn')
            ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
            ->join('products as p', 'cdni.product_id', '=', 'p.id')
            ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->leftJoin('invoices as i', 'cdn.invoice_id', '=', 'i.id')
            ->where('cdn.team_id', $team_id)
            ->where('cdn.season_id', $season_id)
            ->where('cdn.affects_inventory', 1)
            ->when($company_reason_id, function ($q) use ($company_reason_id) {
                $q->where(function ($w) use ($company_reason_id) {
                    $w->whereIn('i.company_reason_id', $company_reason_id)
                      ->orWhereNull('i.company_reason_id');
                });
            })
            ->select(
                DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                DB::raw('COALESCE(l3.name, "Sin Clasificar") as level3_name'),
                DB::raw('MONTH(cdn.date) as month_id'),
                'cdn.type',
                DB::raw('SUM(cdni.unit_price * cdni.quantity) as total')
            )
            ->groupBy('level1_name', 'level2_name', 'level3_name', DB::raw('MONTH(cdn.date)'), 'cdn.type')
            ->get();

        foreach ($notesByMonth as $row) {
            $type = strtolower($row->type);
            $signedTotal = ($type === 'credito' || $type === 'nc') ? -$row->total : $row->total;
            $addAmount($row->level1_name, $row->level2_name, $row->level3_name, $row->month_id, $signedTotal);
        }

        return $map;
    }

    /**
     * Detalle por Categoría (Nivel1/2/3) con desglose MENSUAL de Presupuesto, Real (Facturado + Remuneraciones)
     * y Diferencia. Se calcula una sola vez por carga de página; el filtro de meses se aplica en el frontend.
     *
     * @return array [{level1, level2, level3, budget_monthly: float[12], invoiced_monthly: float[12],
     *                 payroll_monthly: float[12], real_monthly: float[12], difference_monthly: float[12], ...totales}]
     */
    private function getComparisonByLevel1Monthly($season_id, $team_id, $company_reason_id, array $months): array
    {
        try {
            $budgetRows  = $this->getBudgetTotalsByLevel12($season_id, $team_id, $company_reason_id, true);
            $invoicedMap = $this->getInvoicedMonthlyByLevel123($season_id, $team_id, $company_reason_id, $months);
            $payrollRows = $this->getPayrollByLevel3Monthly($team_id, $season_id, $months, $company_reason_id);

            $categories = [];
            $emptyRow = function ($level1, $level2, $level3) {
                return [
                    'level1' => $level1, 'level2' => $level2, 'level3' => $level3,
                    'budget_monthly'   => array_fill(0, 12, 0.0),
                    'invoiced_monthly' => array_fill(0, 12, 0.0),
                    'payroll_monthly'  => array_fill(0, 12, 0.0),
                ];
            };

            foreach ($budgetRows as $row) {
                $key = $row['level1_name'] . '||' . $row['level2_name'] . '||' . $row['level3_name'];
                if (!isset($categories[$key])) {
                    $categories[$key] = $emptyRow($row['level1_name'], $row['level2_name'], $row['level3_name']);
                }
                foreach ($row['monthly'] as $i => $v) {
                    $categories[$key]['budget_monthly'][$i] += (float) $v;
                }
            }

            foreach ($invoicedMap as $key => $data) {
                if (!isset($categories[$key])) {
                    $categories[$key] = $emptyRow($data['level1'], $data['level2'], $data['level3']);
                }
                foreach ($data['monthly'] as $i => $v) {
                    $categories[$key]['invoiced_monthly'][$i] += (float) $v;
                }
            }

            foreach ($payrollRows as $data) {
                $key = $data['level1'] . '||' . $data['level2'] . '||' . $data['level3'];
                if (!isset($categories[$key])) {
                    $categories[$key] = $emptyRow($data['level1'], $data['level2'], $data['level3']);
                }
                foreach ($data['monthly'] as $i => $v) {
                    $categories[$key]['payroll_monthly'][$i] += (float) $v;
                }
            }

            $result = [];
            foreach ($categories as $data) {
                $realMonthly = [];
                $differenceMonthly = [];
                for ($i = 0; $i < 12; $i++) {
                    $real = $data['invoiced_monthly'][$i] + $data['payroll_monthly'][$i];
                    $realMonthly[] = $real;
                    $differenceMonthly[] = $data['budget_monthly'][$i] - $real;
                }
                $data['real_monthly']       = $realMonthly;
                $data['difference_monthly'] = $differenceMonthly;
                $data['budget_total']       = array_sum($data['budget_monthly']);
                $data['invoiced_total']     = array_sum($data['invoiced_monthly']);
                $data['payroll_total']      = array_sum($data['payroll_monthly']);
                $data['real_total']         = array_sum($realMonthly);
                $data['difference_total']   = array_sum($differenceMonthly);
                $result[] = $data;
            }

            // Mismo criterio de orden que getComparisonByLevel1()
            usort($result, function ($a, $b) {
                $normalize = function ($str) {
                    $str = strtolower(trim($str));
                    return str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $str);
                };
                $level1Order = [
                    'costos directos' => 1, 'administracion' => 2, 'generales campo' => 3,
                    'cosecha' => 4, 'sin clasificar' => 5,
                ];
                $orderA = $level1Order[$normalize($a['level1'])] ?? 99;
                $orderB = $level1Order[$normalize($b['level1'])] ?? 99;
                if ($orderA !== $orderB) return $orderA - $orderB;
                $cmp = strcmp(strtolower($a['level2']), strtolower($b['level2']));
                if ($cmp !== 0) return $cmp;
                return strcmp(strtolower($a['level3'] ?? ''), strtolower($b['level3'] ?? ''));
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getComparisonByLevel1Monthly: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los totales de presupuesto agrupados por Level1 y Level2
     * Replica la lógica de DashboardController->getTotalsByLevel12()
     * Retorna: [level1_id, level1_name, level2_id, level2_name, total_amount]
     */
    private function getBudgetTotalsByLevel12($season_id, $team_id, $company_reason_id = null, $trackMonthly = false)
    {
        $season = \App\Models\Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $costCentersQuery = \App\Models\CostCenter::where('season_id', $season_id);
        if ($team_id) {
            $costCentersQuery->whereHas('season.team', function ($query) use ($team_id) {
                $query->where('team_id', $team_id);
            });
        }
        // Filtro razón social: incluir CCs con la razón social indicada O sin razón social (prorrateados)
        if ($company_reason_id) {
            $costCentersQuery->where(function ($w) use ($company_reason_id) {
                $w->where('company_reason_id', $company_reason_id)
                  ->orWhereNull('company_reason_id');
            });
        }
        $costCenters = $costCentersQuery->get(['id', 'fruit_id', 'surface'])->keyBy('id');

        // Administración y Generales Campo no tienen CC propio: se atribuyen por su sucursal (branch_id)
        $branchRatios = $this->getBranchCompanyReasonRatios($season_id, $company_reason_id);

        $totals = [];
        
        $addTotal = function ($level1_id, $level1_name, $level2_id, $level2_name, $level3_id, $level3_name, $amount, $monthlyAmounts = null) use (&$totals, $trackMonthly) {
            $key = $level1_id . '-' . $level2_id . '-' . $level3_id;
            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'level1_id' => $level1_id,
                    'level1_name' => $level1_name,
                    'level2_id' => $level2_id,
                    'level2_name' => $level2_name,
                    'level3_id' => $level3_id,
                    'level3_name' => $level3_name,
                    'total_amount' => 0
                ];
                if ($trackMonthly) {
                    $totals[$key]['monthly'] = array_fill(0, 12, 0.0);
                }
            }
            $totals[$key]['total_amount'] += $amount;
            if ($trackMonthly && $monthlyAmounts) {
                foreach ($monthlyAmounts as $i => $v) {
                    $totals[$key]['monthly'][$i] += $v;
                }
            }
        };

        // AGROCHEMICALS
        $agrochemicals = \App\Models\Agrochemical::from('agrochemicals as a')
            ->join('agrochemical_items as ai', 'a.id', 'ai.agrochemical_id')
            ->join('level3s as l3', 'a.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('a.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'ai.cost_center_id')
            ->where('a.season_id', $season_id)
            ->where('a.team_id', $team_id)
            ->whereIn('ai.cost_center_id', $costCenters->keys())
            ->groupBy('a.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'ai.cost_center_id')
            ->get();

        // Pre-cargar existencia de items en UNA sola query (reemplaza N×12 queries individuales)
        $agroItemIndex = [];
        if ($agrochemicals->isNotEmpty()) {
            $agroItemBatch = DB::table('agrochemical_items')
                ->select('agrochemical_id', 'cost_center_id', 'month_id')
                ->whereIn('agrochemical_id', $agrochemicals->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($agroItemBatch as $item) {
                $agroItemIndex[$item->agrochemical_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($agrochemicals as $a) {
            $amount = 0;
            $cc = $costCenters->get($a->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $dose = (($a->unit_id == 4 && $a->unit_id_price == 3) || ($a->unit_id == 2 && $a->unit_id_price == 1)) ? ($a->dose / 1000) : $a->dose;
            
            if ($a->dose_type_id == 1) {
                $quantityFirst = round($dose * $surface, 2);
            } elseif ($a->dose_type_id == 2) {
                $quantityFirst = round((($a->mojamiento / 100) * $dose * $surface), 2);
            } else {
                $quantityFirst = 0;
            }
            
            $amountFirst = round($a->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($agroItemIndex[$a->id][$a->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($a->level1_id, $a->level1_name, $a->level2_id, $a->level2_name, $a->level3_id, $a->level3_name, $amount, $monthlyAmounts);
        }

        // FERTILIZERS
        $fertilizers = \App\Models\Fertilizer::from('fertilizers as f')
            ->join('fertilizer_items as fi', 'f.id', 'fi.fertilizer_id')
            ->join('level3s as l3', 'f.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('f.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'fi.cost_center_id')
            ->where('f.season_id', $season_id)
            ->where('f.team_id', $team_id)
            ->whereIn('fi.cost_center_id', $costCenters->keys())
            ->groupBy('f.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'fi.cost_center_id')
            ->get();

        // Pre-cargar items fertilizantes en batch
        $fertItemIndex = [];
        if ($fertilizers->isNotEmpty()) {
            $fertItemBatch = DB::table('fertilizer_items')
                ->select('fertilizer_id', 'cost_center_id', 'month_id')
                ->whereIn('fertilizer_id', $fertilizers->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($fertItemBatch as $item) {
                $fertItemIndex[$item->fertilizer_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($fertilizers as $f) {
            $amount = 0;
            $cc = $costCenters->get($f->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $dose = (($f->unit_id == 4 && $f->unit_id_price == 3) || ($f->unit_id == 2 && $f->unit_id_price == 1)) ? ($f->dose / 1000) : $f->dose;
            $quantityFirst = round($dose * $surface, 2);
            $amountFirst = round($f->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($fertItemIndex[$f->id][$f->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($f->level1_id, $f->level1_name, $f->level2_id, $f->level2_name, $f->level3_id, $f->level3_name, $amount, $monthlyAmounts);
        }

        // MANPOWER
        $manpowers = \App\Models\ManPower::from('man_powers as mp')
            ->join('manpower_items as mpi', 'mp.id', 'mpi.man_power_id')
            ->join('level3s as l3', 'mp.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('mp.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'mpi.cost_center_id')
            ->where('mp.season_id', $season_id)
            ->where('mp.team_id', $team_id)
            ->whereIn('mpi.cost_center_id', $costCenters->keys())
            ->groupBy('mp.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'mpi.cost_center_id')
            ->get();

        // Pre-cargar items mano de obra en batch
        $mpItemIndex = [];
        if ($manpowers->isNotEmpty()) {
            $mpItemBatch = DB::table('manpower_items')
                ->select('man_power_id', 'cost_center_id', 'month_id')
                ->whereIn('man_power_id', $manpowers->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('man_power_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($mpItemBatch as $item) {
                $mpItemIndex[$item->man_power_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($manpowers as $mp) {
            $amount = 0;
            $cc = $costCenters->get($mp->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $quantityFirst = round($mp->workday * $surface, 2);
            $amountFirst = round($mp->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($mpItemIndex[$mp->id][$mp->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($mp->level1_id, $mp->level1_name, $mp->level2_id, $mp->level2_name, $mp->level3_id, $mp->level3_name, $amount, $monthlyAmounts);
        }

        // SUPPLIES
        $supplies = \App\Models\Supply::from('supplies as s')
            ->join('supply_items as si', 's.id', 'si.supply_id')
            ->join('level3s as l3', 's.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('s.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'si.cost_center_id')
            ->where('s.season_id', $season_id)
            ->where('s.team_id', $team_id)
            ->whereIn('si.cost_center_id', $costCenters->keys())
            ->groupBy('s.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'si.cost_center_id')
            ->get();

        // Pre-cargar items insumos en batch
        $supItemIndex = [];
        if ($supplies->isNotEmpty()) {
            $supItemBatch = DB::table('supply_items')
                ->select('supply_id', 'cost_center_id', 'month_id')
                ->whereIn('supply_id', $supplies->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('supply_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($supItemBatch as $item) {
                $supItemIndex[$item->supply_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($supplies as $s) {
            $amount = 0;
            $cc = $costCenters->get($s->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $quantity = ($s->unit_id !== null && in_array($s->unit_id, [2, 4])) ? ($s->quantity / 1000) : $s->quantity;
            $quantityFirst = round($quantity * $surface, 2);
            $amountFirst = round($s->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($supItemIndex[$s->id][$s->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($s->level1_id, $s->level1_name, $s->level2_id, $s->level2_name, $s->level3_id, $s->level3_name, $amount, $monthlyAmounts);
        }

        // SERVICES
        $services = \App\Models\Service::from('services as srv')
            ->join('service_items as si', 'srv.id', 'si.service_id')
            ->join('level3s as l3', 'srv.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('srv.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'si.cost_center_id')
            ->where('srv.season_id', $season_id)
            ->where('srv.team_id', $team_id)
            ->whereIn('si.cost_center_id', $costCenters->keys())
            ->groupBy('srv.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'si.cost_center_id')
            ->get();

        // Pre-cargar items servicios en batch
        $srvItemIndex = [];
        if ($services->isNotEmpty()) {
            $srvItemBatch = DB::table('service_items')
                ->select('service_id', 'cost_center_id', 'month_id')
                ->whereIn('service_id', $services->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('service_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($srvItemBatch as $item) {
                $srvItemIndex[$item->service_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($services as $srv) {
            $amount = 0;
            $cc = $costCenters->get($srv->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $quantityFirst = round($srv->quantity * $surface, 2);
            $amountFirst = round($srv->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($srvItemIndex[$srv->id][$srv->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($srv->level1_id, $srv->level1_name, $srv->level2_id, $srv->level2_name, $srv->level3_id, $srv->level3_name, $amount, $monthlyAmounts);
        }

        // HARVESTS
        $harvests = \App\Models\Harvest::from('harvests as h')
            ->join('harvest_items as hi', 'h.id', 'hi.harvest_id')
            ->join('level3s as l3', 'h.subfamily_id', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', 'l1.id')
            ->select('h.*', 'l1.id as level1_id', 'l1.name as level1_name', 'l2.id as level2_id', 'l2.name as level2_name', 'l3.id as level3_id', 'l3.name as level3_name', 'hi.cost_center_id')
            ->where('h.season_id', $season_id)
            ->where('h.team_id', $team_id)
            ->whereIn('hi.cost_center_id', $costCenters->keys())
            ->groupBy('h.id', 'l1.id', 'l1.name', 'l2.id', 'l2.name', 'l3.id', 'l3.name', 'hi.cost_center_id')
            ->get();

        // Pre-cargar items cosecha en batch
        $harvItemIndex = [];
        if ($harvests->isNotEmpty()) {
            $harvItemBatch = DB::table('harvest_items')
                ->select('harvest_id', 'cost_center_id', 'month_id')
                ->whereIn('harvest_id', $harvests->pluck('id')->unique())
                ->whereIn('cost_center_id', $costCenters->keys())
                ->whereIn('month_id', $months)
                ->groupBy('harvest_id', 'cost_center_id', 'month_id')
                ->get();
            foreach ($harvItemBatch as $item) {
                $harvItemIndex[$item->harvest_id][$item->cost_center_id][$item->month_id] = true;
            }
        }

        foreach ($harvests as $h) {
            $amount = 0;
            $cc = $costCenters->get($h->cost_center_id);
            $surface = $cc ? $cc->surface : 0;
            
            $quantityFirst = round($h->quantity * $surface, 2);
            $amountFirst = round($h->price * $quantityFirst, 2);
            
            $monthlyAmounts = array_fill(0, 12, 0.0);
            foreach ($months as $idx => $month) {
                $exists = isset($harvItemIndex[$h->id][$h->cost_center_id][$month]);
                $monthlyAmounts[$idx] = $exists ? $amountFirst : 0;
                $amount += $monthlyAmounts[$idx];
            }
            
            $addTotal($h->level1_id, $h->level1_name, $h->level2_id, $h->level2_name, $h->level3_id, $h->level3_name, $amount, $monthlyAmounts);
        }

        // ADMINISTRATIONS
        $administrations = DB::table('administrations as a')
            ->join('level3s as l3', 'a.subfamily_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->select(
                'l1.id as level1_id',
                'l1.name as level1_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'l3.id as level3_id',
                'l3.name as level3_name',
                'a.id as administration_id',
                'a.price',
                'a.quantity',
                'a.unit_id',
                'a.branch_id'
            )
            ->where('a.season_id', $season_id)
            ->where('a.team_id', $team_id)
            ->get();

        // Pre-cargar meses activos de administración en batch (con detalle de qué month_id específico)
        $adminMonthsIndex = DB::table('administration_items')
            ->select('administration_id', 'month_id')
            ->whereIn('administration_id', $administrations->pluck('administration_id'))
            ->whereIn('month_id', $months)
            ->groupBy('administration_id', 'month_id')
            ->get()
            ->groupBy('administration_id');

        foreach ($administrations as $adm) {
            $activeMonths = $adminMonthsIndex->get($adm->administration_id, collect())->pluck('month_id')->toArray();
            $countMonths = count($activeMonths);
            if ($countMonths > 0) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2, 4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $ratio = !$company_reason_id ? 1.0 : (is_null($adm->branch_id) ? 0.0 : ($branchRatios[$adm->branch_id] ?? 0.0));
                $amountPerMonth = round($adm->price * $quantity * $ratio, 2);
                $monthlyAmounts = array_fill(0, 12, 0.0);
                foreach ($months as $idx => $month) {
                    if (in_array($month, $activeMonths)) {
                        $monthlyAmounts[$idx] = $amountPerMonth;
                    }
                }
                $amount = round($amountPerMonth * $countMonths, 2);
                $addTotal($adm->level1_id, $adm->level1_name, $adm->level2_id, $adm->level2_name, $adm->level3_id, $adm->level3_name, $amount, $monthlyAmounts);
            }
        }

        // FIELDS
        $fields = DB::table('fields as f')
            ->join('level3s as l3', 'f.subfamily_id', '=', 'l3.id')
            ->join('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->join('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->select(
                'l1.id as level1_id',
                'l1.name as level1_name',
                'l2.id as level2_id',
                'l2.name as level2_name',
                'l3.id as level3_id',
                'l3.name as level3_name',
                'f.id as field_id',
                'f.price',
                'f.quantity',
                'f.unit_id',
                'f.branch_id'
            )
            ->where('f.season_id', $season_id)
            ->where('f.team_id', $team_id)
            ->get();

        // Pre-cargar meses activos de fields en batch (con detalle de qué month_id específico)
        $fieldMonthsIndex = DB::table('field_items')
            ->select('field_id', 'month_id')
            ->whereIn('field_id', $fields->pluck('field_id'))
            ->whereIn('month_id', $months)
            ->groupBy('field_id', 'month_id')
            ->get()
            ->groupBy('field_id');

        foreach ($fields as $fld) {
            $activeMonths = $fieldMonthsIndex->get($fld->field_id, collect())->pluck('month_id')->toArray();
            $countMonths = count($activeMonths);
            if ($countMonths > 0) {
                $quantity = ($fld->quantity !== null && ($fld->quantity > 0)) ? ((in_array($fld->unit_id ?? null, [2, 4])) ? ($fld->quantity / 1000) : $fld->quantity) : 0;
                $ratio = !$company_reason_id ? 1.0 : (is_null($fld->branch_id) ? 0.0 : ($branchRatios[$fld->branch_id] ?? 0.0));
                $amountPerMonth = round($fld->price * $quantity * $ratio, 2);
                $monthlyAmounts = array_fill(0, 12, 0.0);
                foreach ($months as $idx => $month) {
                    if (in_array($month, $activeMonths)) {
                        $monthlyAmounts[$idx] = $amountPerMonth;
                    }
                }
                $amount = round($amountPerMonth * $countMonths, 2);
                $addTotal($fld->level1_id, $fld->level1_name, $fld->level2_id, $fld->level2_name, $fld->level3_id, $fld->level3_name, $amount, $monthlyAmounts);
            }
        }

        return collect(array_values($totals));
    }

    /**
     * Normalizar nombre de categoria para comparacion
     */
    private function normalizeCategory($name)
    {
        // Convertir a minusculas
        $normalized = strtolower(trim($name));
        
        // Quitar tildes
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $normalized
        );
        
        // Quitar puntos y espacios multiples
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = str_replace('.', '', $normalized);
        
        return $normalized;
    }

    /**
     * Mapear nombres de Level2 a categorías de presupuesto
     */
    private function mapLevel2ToCategory($level2Name)
    {
        // Normalizar: quitar tildes, minúsculas, espacios
        $normalized = strtolower(trim($level2Name));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);
        
        $map = [
            'agroquimicos' => 'Agroquímicos',
            'agroquimico' => 'Agroquímicos',
            'fertilizantes' => 'Fertilizantes',
            'fertilizante' => 'Fertilizantes',
            'mano de obra' => 'Mano de Obra',
            'mano obra' => 'Mano de Obra',
            'servicios' => 'Servicios',
            'servicio' => 'Servicios',
            'insumos' => 'Insumos',
            'insumo' => 'Insumos',
            'cosecha' => 'Cosecha',
        ];
        
        return $map[$normalized] ?? null;
    }

    // ========================================
    // MÉTODOS QUE REPLICAN TechnicalPanelController
    // ========================================

    private function getAgrochemicalProductsForComparison($costCentersId, $currentMonth, &$monthsAgrochemical)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('agrochemical_items')
            ->select('agrochemical_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('agrochemical_id', 'cost_center_id', 'month_id')
            ->get();

        $products = Agrochemical::from('agrochemicals as a')
            ->leftJoin('units as u', 'a.unit_id_price', 'u.id')
            ->select('a.id', 'a.price', 'a.dose_type_id', 'a.dose', 'a.unit_id', 'a.unit_id_price', 'a.mojamiento')
            ->whereIn('a.id', $items->pluck('agrochemical_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->agrochemical_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                if ($value->dose_type_id == 1) {
                    $quantityFirst = round($dose * $surface, 2);
                } elseif ($value->dose_type_id == 2) {
                    $quantityFirst = round((($value->mojamiento / 100) * $dose * $surface), 2);
                } else {
                    $quantityFirst = 0;
                }
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsAgrochemical[$month])) {
                        $monthsAgrochemical[$month] = 0;
                    }
                    $monthsAgrochemical[$month] += $amountMonth;
                }
            }
        }
    }

    private function getFertilizerProductsForComparison($costCentersId, $currentMonth, &$monthsFertilizer)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('fertilizer_items')
            ->select('fertilizer_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('fertilizer_id', 'cost_center_id', 'month_id')
            ->get();

        $products = Fertilizer::from('fertilizers as f')
            ->leftJoin('units as u', 'f.unit_id_price', 'u.id')
            ->select('f.id', 'f.price', 'f.dose', 'f.unit_id', 'f.unit_id_price')
            ->whereIn('f.id', $items->pluck('fertilizer_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->fertilizer_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $dose = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->dose / 1000) : $value->dose;
                $quantityFirst = round($dose * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsFertilizer[$month])) {
                        $monthsFertilizer[$month] = 0;
                    }
                    $monthsFertilizer[$month] += $amountMonth;
                }
            }
        }
    }

    private function getManPowerProductsForComparison($costCentersId, $currentMonth, &$monthsManPower)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('manpower_items')
            ->select('man_power_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('man_power_id', 'cost_center_id', 'month_id')
            ->get();

        $products = ManPower::from('man_powers as mp')
            ->leftJoin('units as u', 'mp.unit_id', 'u.id')
            ->select('mp.id', 'mp.price', 'mp.workday')
            ->whereIn('mp.id', $items->pluck('man_power_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->man_power_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $quantityFirst = round($value->workday * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsManPower[$month])) {
                        $monthsManPower[$month] = 0;
                    }
                    $monthsManPower[$month] += $amountMonth;
                }
            }
        }
    }

    private function getServicesProductsForComparison($costCentersId, $currentMonth, &$monthsServices)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('service_items')
            ->select('service_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('service_id', 'cost_center_id', 'month_id')
            ->get();

        $products = Service::from('services as s')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->whereIn('s.id', $items->pluck('service_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->service_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsServices[$month])) {
                        $monthsServices[$month] = 0;
                    }
                    $monthsServices[$month] += $amountMonth;
                }
            }
        }
    }

    private function getSuppliesProductsForComparison($costCentersId, $currentMonth, &$monthsSupplies)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('supply_items')
            ->select('supply_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('supply_id', 'cost_center_id', 'month_id')
            ->get();

        $products = Supply::from('supplies as s')
            ->leftJoin('units as u', 's.unit_id_price', 'u.id')
            ->select('s.id', 's.product_name', 's.price', 's.quantity', 's.unit_id', 's.unit_id_price', 'u.name')
            ->whereIn('s.id', $items->pluck('supply_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->supply_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsSupplies[$month])) {
                        $monthsSupplies[$month] = 0;
                    }
                    $monthsSupplies[$month] += $amountMonth;
                }
            }
        }
    }

    private function getHarvestsProductsForComparison($costCentersId, $currentMonth, &$monthsHarvests)
    {
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }

        $items = DB::table('harvest_items')
            ->select('harvest_id', 'cost_center_id', 'month_id', DB::raw('COUNT(*) as count'))
            ->whereIn('cost_center_id', $costCentersId)
            ->whereIn('month_id', $months)
            ->groupBy('harvest_id', 'cost_center_id', 'month_id')
            ->get();

        $products = Harvest::from('harvests as h')
            ->leftJoin('units as u', 'h.unit_id_price', 'u.id')
            ->select('h.id', 'h.product_name', 'h.price', 'h.quantity', 'h.unit_id', 'h.unit_id_price', 'u.name')
            ->whereIn('h.id', $items->pluck('harvest_id')->unique())
            ->get();

        $surfaces = CostCenter::whereIn('id', $costCentersId)->pluck('surface', 'id');

        $itemIndex = [];
        foreach ($items as $item) {
            $itemIndex[$item->harvest_id][$item->cost_center_id][$item->month_id] = $item->count;
        }

        foreach ($products as $value) {
            foreach ($costCentersId as $costCenter) {
                $surface = isset($surfaces[$costCenter]) ? $surfaces[$costCenter] : 0;
                $quantity = (($value->unit_id == 4 && $value->unit_id_price == 3) || ($value->unit_id == 2 && $value->unit_id_price == 1)) ? ($value->quantity / 1000) : $value->quantity;
                $quantityFirst = round($quantity * $surface, 2);
                $amountFirst = round($value->price * $quantityFirst, 2);
                foreach ($months as $month) {
                    $count = isset($itemIndex[$value->id][$costCenter][$month]) ? $itemIndex[$value->id][$costCenter][$month] : 0;
                    $amountMonth = $count > 0 ? $amountFirst : 0;
                    if (!isset($monthsHarvests[$month])) {
                        $monthsHarvests[$month] = 0;
                    }
                    $monthsHarvests[$month] += $amountMonth;
                }
            }
        }
    }

    /**
     * Calcula, por sucursal (branch_id), el ratio de superficie que corresponde
     * a las razones sociales filtradas. Una sucursal puede tener centros de costo
     * de más de una razón social, por eso se prorratea a nivel de esa sucursal
     * específica (no sobre el total de superficie de todo el equipo).
     */
    private function getBranchCompanyReasonRatios($season_id, $company_reason_id)
    {
        if (!$company_reason_id) {
            return [];
        }

        $branchSurfaces = DB::table('cost_centers')
            ->where('season_id', $season_id)
            ->whereNotNull('branch_id')
            ->select('branch_id', 'company_reason_id', DB::raw('SUM(surface) as surface'))
            ->groupBy('branch_id', 'company_reason_id')
            ->get()
            ->groupBy('branch_id');

        $ratios = [];
        foreach ($branchSurfaces as $branchId => $rows) {
            $total = $rows->sum('surface');
            $filtered = $rows->filter(function ($r) use ($company_reason_id) {
                return is_null($r->company_reason_id) || in_array($r->company_reason_id, $company_reason_id);
            })->sum('surface');
            $ratios[$branchId] = $total > 0 ? ($filtered / $total) : 0.0;
        }
        return $ratios;
    }

    private function getMonthsAdministration($team_id, $company_reason_id = null)
    {
        $season_id = session('season_id');
        $season = Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $result = array_fill_keys($months, 0);

        // Ratio de atribución por sucursal según razón social filtrada
        $branchRatios = $this->getBranchCompanyReasonRatios($season_id, $company_reason_id);

        // Batch: JOIN administrations con items agrupados por mes y sucursal
        $query = DB::table('administrations as a')
            ->join('administration_items as ai', 'a.id', '=', 'ai.administration_id')
            ->select(
                'ai.month_id',
                'a.branch_id',
                DB::raw('SUM(ROUND(a.price * CASE
                    WHEN a.quantity IS NOT NULL AND a.quantity > 0 THEN
                        CASE WHEN a.unit_id IN (2, 4) THEN a.quantity / 1000 ELSE a.quantity END
                    ELSE 0
                END, 2)) as total')
            )
            ->where('a.season_id', $season_id)
            ->whereIn('ai.month_id', $months);
        if ($team_id) {
            $query->where('a.team_id', $team_id);
        }
        $rows = $query->groupBy('ai.month_id', 'a.branch_id')->get();

        foreach ($rows as $row) {
            if (!isset($result[$row->month_id])) {
                continue;
            }
            // Sin razón social filtrada: 100%. Con filtro, sin sucursal asignada: se excluye (igual que fields.index al filtrar por sucursal)
            $ratio = !$company_reason_id
                ? 1.0
                : (is_null($row->branch_id) ? 0.0 : ($branchRatios[$row->branch_id] ?? 0.0));
            $result[$row->month_id] += floatval($row->total) * $ratio;
        }
        return $result;
    }

    private function getMonthsFields($team_id, $company_reason_id = null)
    {
        $season_id = session('season_id');
        $season = Season::select('month_id')->where('id', $season_id)->first();
        $currentMonth = $season ? $season->month_id : 1;
        $months = [];
        for ($x = $currentMonth; $x < $currentMonth + 12; $x++) {
            $id = date('n', mktime(0, 0, 0, $x, 1));
            $months[] = $id;
        }
        $result = array_fill_keys($months, 0);

        // Ratio de atribución por sucursal según razón social filtrada
        $branchRatios = $this->getBranchCompanyReasonRatios($season_id, $company_reason_id);

        // Batch: JOIN fields con items agrupados por mes y sucursal
        $query = DB::table('fields as f')
            ->join('field_items as fi', 'f.id', '=', 'fi.field_id')
            ->select(
                'fi.month_id',
                'f.branch_id',
                DB::raw('SUM(ROUND(f.price * CASE
                    WHEN f.quantity IS NOT NULL AND f.quantity > 0 THEN
                        CASE WHEN f.unit_id IN (2, 4) THEN f.quantity / 1000 ELSE f.quantity END
                    ELSE 0
                END, 2)) as total')
            )
            ->where('f.season_id', $season_id)
            ->whereIn('fi.month_id', $months);
        if ($team_id) {
            $query->where('f.team_id', $team_id);
        }
        $rows = $query->groupBy('fi.month_id', 'f.branch_id')->get();

        foreach ($rows as $row) {
            if (!isset($result[$row->month_id])) {
                continue;
            }
            $ratio = !$company_reason_id
                ? 1.0
                : (is_null($row->branch_id) ? 0.0 : ($branchRatios[$row->branch_id] ?? 0.0));
            $result[$row->month_id] += floatval($row->total) * $ratio;
        }
        return $result;
    }
}
