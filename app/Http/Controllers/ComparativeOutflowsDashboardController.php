<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\Invoice;
use App\Models\CreditDebitNote;
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

class ComparativeOutflowsDashboardController extends Controller
{
    use BudgetTotalsTrait;

    public function index()
    {
        $season_id = session('season_id');
        $team_id = Auth::user()->team_id;

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        // Obtener información de la temporada
        $season = Season::with('month')->find($season_id);
        $startMonthId = $season->month_id ?? 1;

        // Generar array de 12 meses desde el mes de inicio
        $months = $this->generateMonthsArray($startMonthId);

        return Inertia::render('ComparativeOutflowsDashboard', [
            'summary' => $this->getSummaryComparison($season_id, $team_id),
            'monthlyComparison' => $this->getMonthlyComparison($season_id, $team_id, $months),
            'cumulativeComparison' => $this->getCumulativeComparison($season_id, $team_id, $months),
            'comparisonByLevel1' => $this->getComparisonByLevel1($season_id, $team_id),
            'comparisonByLevel2' => $this->getComparisonByLevel2($season_id, $team_id),
            'detailedTable' => $this->getDetailedComparisonTable($season_id, $team_id),
            'months' => $months,
            'seasonStartMonth' => $startMonthId,
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
     * Resumen comparativo general
     */
    private function getSummaryComparison($season_id, $team_id)
    {
        try {
            // Total Presupuestado (usando el trait) - 8 categorías SIN inversiones
            $budgetTotal = (float) $this->getTotalField($season_id, $team_id)
                + (float) $this->getTotalAdministration($season_id, $team_id)
                + (float) $this->getTotalFertilizer($season_id, $team_id)
                + (float) $this->getTotalManPower($season_id, $team_id)
                + (float) $this->getTotalAgrochemical($season_id, $team_id)
                + (float) $this->getTotalSupplies($season_id, $team_id)
                + (float) $this->getTotalServices($season_id, $team_id)
                + (float) $this->getTotalHarvest($season_id, $team_id);

            // Total Inversiones (del trait)
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

            // Total Facturado (desde invoices + notas de crédito/débito)
            // Replicando lógica del InvoicePaymentDashboardController y ConsolidatedDocuments
            $invoicesTotal = Invoice::where('team_id', $team_id)
                ->where('season_id', $season_id)
                ->with(['invoiceProducts'])
                ->get()
                ->sum(function($invoice) {
                    return $invoice->invoiceProducts->sum(function($ip) {
                        return $ip->unit_price * $ip->amount;
                    });
                });

            // Notas de crédito/débito
            $notesTotal = CreditDebitNote::where('team_id', $team_id)
                ->where('season_id', $season_id)
                ->with(['items'])
                ->get()
                ->sum(function($note) {
                    $monto = $note->items->sum(function($item) {
                        return $item->unit_price * $item->quantity;
                    });
                    
                    // Si es nota de crédito, restar
                    $type = strtolower($note->type);
                    if ($type === 'credito' || $type === 'nc') {
                        return -$monto;
                    }
                    // Si es nota de débito, sumar
                    return $monto;
                });

            $invoicedTotal = floatval($invoicesTotal + $notesTotal);
            Log::info("getSummaryComparison - Budget: $budgetTotal, Facturado: $invoicedTotal (Invoices: $invoicesTotal + Notes: $notesTotal)");

            // Total Consumido - Calcular ambas versiones (con y sin inversiones)
            // Usar whereHas para filtrar correctamente por operación (igual que OutflowsDashboardController)
            
            // Consumido CON inversiones (todos los outflows)
            $allOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $consumedTotalWithInvestments = $allOutflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            // Consumido SOLO inversiones (usando whereHas igual que OutflowsDashboardController)
            $investmentOutflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $consumedInvestmentsTotal = $investmentOutflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            // Consumido SIN inversiones = Total - Inversiones
            $consumedTotal = $consumedTotalWithInvestments - $consumedInvestmentsTotal;

            Log::info("getSummaryComparison - Total Outflows: " . $allOutflows->count());
            Log::info("getSummaryComparison - Consumido (sin inversiones): $consumedTotal");
            Log::info("getSummaryComparison - Consumido (con inversiones): $consumedTotalWithInvestments");
            Log::info("getSummaryComparison - Inversiones consumidas: $consumedInvestmentsTotal");

            // Usar facturado para cálculos principales
            $realTotal = $invoicedTotal;
            $difference = $budgetTotal - $realTotal;
            $percentageExecution = $budgetTotal > 0 ? ($realTotal / $budgetTotal) * 100 : 0;
            $variance = $budgetTotal > 0 ? (($realTotal - $budgetTotal) / $budgetTotal) * 100 : 0;

            // Obtener superficie total para cálculos por hectárea
            $totalSurface = DB::table('cost_centers')
                ->where('season_id', $season_id)
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
    private function getMonthlyComparison($season_id, $team_id, $months)
    {
        try {
            // Usar la misma lógica que TechnicalPanelController
            $user = Auth::user();
            $season = Season::select('name', 'month_id')->where('id', $season_id)->first();
            $month_id = $season ? $season->month_id : 1;
            
            // Obtener cost centers igual que TechnicalPanelController
            $costCenters = CostCenter::select('id', 'name')
                ->where('season_id', $season_id)
                ->whereHas('season.team', function($query) use ($team_id) {
                    $query->where('team_id', $team_id);
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
            $monthsAdministration = $this->getMonthsAdministration($team_id);
            $monthsFields = $this->getMonthsFields($team_id);

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

                // Facturado del mes (usando fecha de factura)
                $invoicedMonth = $this->getInvoicedForMonth($season_id, $team_id, $monthId);
                $realByMonth[] = floatval($invoicedMonth);

                // Consumido del mes (desde outflows)
                $consumedData = $this->getConsumedForMonth($season_id, $team_id, $monthId);
                $consumedByMonth[] = floatval($consumedData['total']);
                $consumedWithInvestmentsByMonth[] = floatval($consumedData['total_with_investments']);

                Log::info("Mes {$month['name']} (ID: $monthId) - Budget: $budgetMonth, Con Inv: " . ($budgetMonth + $investmentMonth) . ", Facturado: $invoicedMonth, Consumido: {$consumedData['total']}, Consumido con Inv: {$consumedData['total_with_investments']}");
            }

            Log::info('Monthly Comparison - Budget Total: ' . array_sum($budgetByMonth));
            Log::info('Monthly Comparison - Budget with Investments Total: ' . array_sum($budgetWithInvestmentsByMonth));
            Log::info('Monthly Comparison - Facturado Total: ' . array_sum($realByMonth));

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
    private function getCumulativeComparison($season_id, $team_id, $months)
    {
        try {
            $monthlyData = $this->getMonthlyComparison($season_id, $team_id, $months);
            
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

            Log::info('Cumulative Comparison - Last month with data: ' . $lastMonthWithData);
            Log::info('Cumulative Comparison - Last month with consumed data: ' . $lastMonthWithConsumedData);
            Log::info('Cumulative Comparison - Budget: ' . json_encode($budgetCumulative));
            Log::info('Cumulative Comparison - Budget with Investments: ' . json_encode($budgetWithInvestmentsCumulative));
            Log::info('Cumulative Comparison - Real: ' . json_encode($realCumulative));
            Log::info('Cumulative Comparison - Consumed: ' . json_encode($consumedCumulative));
            Log::info('Cumulative Comparison - Consumed with Investments: ' . json_encode($consumedWithInvestmentsCumulative));

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
            Log::error('Error en ComparativeDashboard getCumulativeComparison: ' . $e->getMessage());
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

            // Total de notas de crédito/débito del mes
            $notesTotal = CreditDebitNote::where('team_id', $team_id)
                ->where('season_id', $season_id)
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
    private function getComparisonByLevel1($season_id, $team_id)
    {
        try {
            // Inicializar array para almacenar todas las categorías encontradas
            $categories = [];

            // ========================================
            // 1. PRESUPUESTO por categoría (las 6 principales + Administración + Gral Campo)
            // ========================================
            $budgetCategories = [
                'Costos Directos - Agroquímicos' => (float) $this->getTotalAgrochemical($season_id, $team_id),
                'Costos Directos - Fertilizantes' => (float) $this->getTotalFertilizer($season_id, $team_id),
                'Costos Directos - Mano de Obra' => (float) $this->getTotalManPower($season_id, $team_id),
                'Costos Directos - Servicios' => (float) $this->getTotalServices($season_id, $team_id),
                'Costos Directos - Insumos' => (float) $this->getTotalSupplies($season_id, $team_id),
                'Cosecha - Cosecha' => (float) $this->getTotalHarvest($season_id, $team_id),
                'Administración - Administración' => (float) $this->getTotalAdministration($season_id, $team_id),
                'Generales Campo - Gral. Campo' => (float) $this->getTotalField($season_id, $team_id),
            ];

            // Mapa de categorías normalizadas a nombre display
            $categoryMap = [];
            
            foreach ($budgetCategories as $name => $budget) {
                // Extraer solo el level2 para normalización (después del guión)
                $parts = explode(' - ', $name);
                $level2Name = count($parts) > 1 ? $parts[1] : $name;
                $normalizedKey = $this->normalizeCategory($level2Name);
                $categoryMap[$normalizedKey] = $name; // Guardar el nombre completo
                
                $categories[$name] = [
                    'budget' => $budget,
                    'invoiced' => 0,
                    'consumed' => 0
                ];
            }

            // ========================================
            // 2. FACTURADO por categoría (Level2 real de la BD)
            // ========================================
            
            // Facturas
            $invoicesByLevel2 = DB::table('invoices as i')
                ->join('invoice_products as ip', 'i.id', '=', 'ip.invoice_id')
                ->join('products as p', 'ip.product_id', '=', 'p.id')
                ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('i.team_id', $team_id)
                ->where('i.season_id', $season_id)
                ->select(
                    DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                    DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                    DB::raw('SUM(ip.unit_price * ip.amount) as total')
                )
                ->groupBy('level1_name', 'level2_name')
                ->get();

            foreach ($invoicesByLevel2 as $row) {
                $level1Name = $row->level1_name;
                $level2Name = $row->level2_name;
                $fullName = $level1Name . ' - ' . $level2Name;
                
                $normalizedKey = $this->normalizeCategory($level2Name);
                
                // Buscar si existe una categoría con el mismo nombre normalizado
                $categoryName = $categoryMap[$normalizedKey] ?? $fullName;
                
                // Si la categoría no existe, crearla y registrar en el mapa
                if (!isset($categories[$categoryName])) {
                    $categories[$categoryName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                    $categoryMap[$normalizedKey] = $categoryName;
                }
                
                $categories[$categoryName]['invoiced'] += floatval($row->total);
            }

            // Notas de Crédito/Débito
            $notesByLevel2 = DB::table('credit_debit_notes as cdn')
                ->join('credit_debit_note_items as cdni', 'cdn.id', '=', 'cdni.credit_debit_note_id')
                ->join('products as p', 'cdni.product_id', '=', 'p.id')
                ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
                ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
                ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
                ->where('cdn.team_id', $team_id)
                ->where('cdn.season_id', $season_id)
                ->select(
                    DB::raw('COALESCE(l1.name, "Sin Clasificar") as level1_name'),
                    DB::raw('COALESCE(l2.name, "Sin Clasificar") as level2_name'),
                    'cdn.type',
                    DB::raw('SUM(cdni.unit_price * cdni.quantity) as total')
                )
                ->groupBy('level1_name', 'level2_name', 'cdn.type')
                ->get();

            foreach ($notesByLevel2 as $row) {
                $level1Name = $row->level1_name;
                $level2Name = $row->level2_name;
                $fullName = $level1Name . ' - ' . $level2Name;
                
                $normalizedKey = $this->normalizeCategory($level2Name);
                
                // Buscar si existe una categoría con el mismo nombre normalizado
                $categoryName = $categoryMap[$normalizedKey] ?? $fullName;
                
                // Si la categoría no existe, crearla y registrar en el mapa
                if (!isset($categories[$categoryName])) {
                    $categories[$categoryName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                    $categoryMap[$normalizedKey] = $categoryName;
                }
                
                $monto = floatval($row->total);
                $type = strtolower($row->type);
                
                if ($type === 'credito' || $type === 'nc') {
                    $categories[$categoryName]['invoiced'] -= $monto;
                } else {
                    $categories[$categoryName]['invoiced'] += $monto;
                }
            }

            // ========================================
            // 3. CONSUMIDO por categoría (Level2 real de la BD)
            // ========================================
            
            $outflowsByLevel2 = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereDoesntHave('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with(['level3.level2.level1', 'invoiceProduct', 'creditDebitNoteItem'])
                ->get()
                ->groupBy(function($outflow) {
                    if ($outflow->level3 && $outflow->level3->level2) {
                        $level1Name = $outflow->level3->level2->level1 ? $outflow->level3->level2->level1->name : 'Sin Clasificar';
                        $level2Name = $outflow->level3->level2->name;
                        return $level1Name . ' - ' . $level2Name;
                    }
                    return 'Sin Clasificar';
                });

            foreach ($outflowsByLevel2 as $fullCategoryName => $outflows) {
                $total = $outflows->sum(function($outflow) {
                    if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                        return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                    }
                    if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                        return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                    }
                    return 0;
                });

                // Extraer solo el level2 para normalización (después del guión)
                $parts = explode(' - ', $fullCategoryName);
                $level2Name = count($parts) > 1 ? $parts[1] : $fullCategoryName;
                $normalizedKey = $this->normalizeCategory($level2Name);
                
                // Buscar si existe una categoría con el mismo nombre normalizado
                $categoryName = $categoryMap[$normalizedKey] ?? $fullCategoryName;
                
                // Si la categoría no existe, crearla y registrar en el mapa
                if (!isset($categories[$categoryName])) {
                    $categories[$categoryName] = [
                        'budget' => 0,
                        'invoiced' => 0,
                        'consumed' => 0
                    ];
                    $categoryMap[$normalizedKey] = $categoryName;
                }
                
                $categories[$categoryName]['consumed'] += floatval($total);
            }

            Log::info('Categorías finales:', ['count' => count($categories)]);

            // Calcular variaciones (usando facturado como "real")
            $result = [];
            foreach ($categories as $name => $data) {
                // Extraer level1 y level2 del nombre
                $parts = explode(' - ', $name);
                $level1Name = count($parts) > 1 ? $parts[0] : 'Sin Clasificar';
                $level2Name = count($parts) > 1 ? $parts[1] : $name;
                
                $variance = $data['budget'] > 0 
                    ? (($data['invoiced'] - $data['budget']) / $data['budget']) * 100 
                    : 0;
                
                $result[] = [
                    'category' => $name,
                    'level1' => $level1Name,
                    'level2' => $level2Name,
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
                
                // Si el level1 es igual, ordenar por level2 alfabéticamente
                return strcmp(strtolower($a['level2']), strtolower($b['level2']));
            });

            return $result;

        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getComparisonByLevel1: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Comparación por Level2 (top 10)
     */
    private function getComparisonByLevel2($season_id, $team_id)
    {
        try {
            // Similar a Level1 pero agrupando por Level2
            // Implementación simplificada
            return [];
        } catch (\Exception $e) {
            Log::error('Error en ComparativeDashboard getComparisonByLevel2: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tabla detallada para exportar
     */
    private function getDetailedComparisonTable($season_id, $team_id)
    {
        return $this->getComparisonByLevel1($season_id, $team_id);
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

    private function getMonthsAdministration($team_id)
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
        $administrations = DB::table('administrations as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $administrations->where('a.team_id', $team_id);
        }
        $administrations = $administrations->get();
        foreach ($administrations as $adm) {
            $items = DB::table('administration_items')
                ->where('administration_id', $adm->id)
                ->whereIn('month_id', $months)
                ->get();
            foreach ($items as $item) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity, 2);
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }

    private function getMonthsFields($team_id)
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
        $fields = DB::table('fields as a')
            ->select('a.id', 'a.price', 'a.quantity', 'a.unit_id')
            ->where('a.season_id', $season_id);
        if ($team_id) {
            $fields->where('a.team_id', $team_id);
        }
        $fields = $fields->get();
        foreach ($fields as $adm) {
            $items = DB::table('field_items')
                ->where('field_id', $adm->id)
                ->whereIn('month_id', $months)
                ->get();
            foreach ($items as $item) {
                $quantity = ($adm->quantity !== null && ($adm->quantity > 0)) ? ((in_array($adm->unit_id ?? null, [2,4])) ? ($adm->quantity / 1000) : $adm->quantity) : 0;
                $amount = round($adm->price * $quantity, 2);
                if (isset($result[$item->month_id])) {
                    $result[$item->month_id] += $amount;
                }
            }
        }
        return $result;
    }
}
