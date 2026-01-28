<?php

namespace App\Http\Controllers;

use App\Models\CreditDebitNote;
use App\Models\Invoice;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Controllers\Traits\BudgetTotalsTrait;

class OutflowsDashboardController extends Controller
{
    use BudgetTotalsTrait;
    public function index(Request $request)
    {
        $season_id = session('season_id');
        $team_id = Auth::user()->team_id;

        // Validar que exista season_id en sesión
        // Nota: La ruta 'select.budget' es donde el usuario selecciona la temporada (season)
        // El nombre es histórico y se mantiene por compatibilidad
        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        return Inertia::render('OutflowsDashboard', [
            'summary' => $this->getSummary($season_id, $team_id),
            'investments' => $this->getInvestmentsTotal($season_id, $team_id),
            'expenses' => $this->getExpensesTotal($season_id, $team_id),
            'invoices' => $this->getInvoicesTotal($season_id, $team_id),
            'creditNotes' => $this->getCreditNotesTotal($season_id, $team_id),
            'debitNotes' => $this->getDebitNotesTotal($season_id, $team_id),
            'byLevel1' => $this->getOutflowsByLevel1($season_id, $team_id),
            'byLevel2' => $this->getOutflowsByLevel2($season_id, $team_id),
            'byProject' => $this->getOutflowsByProject($season_id, $team_id),
            'byDevelopmentState' => $this->getTotalsByDevelopmentState($season_id, $team_id),
            'byDevelopmentStateWithoutInvestments' => $this->getTotalsByDevelopmentStateWithoutInvestments($season_id, $team_id),
            'costoKiloAcumulado' => $this->getCostoKiloAcumulado($season_id, $team_id),
        ]);
    }

    private function getSummary($season_id, $team_id)
    {
        try {
            // Obtener todos los outflows con sus relaciones
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price de cada outflow
            $totalAmount = $outflows->sum(function($outflow) {
                // Si viene de invoice_product
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                // Si viene de credit_debit_note_item
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total_amount' => floatval($totalAmount ?? 0),
                'total_count' => intval($totalCount ?? 0),
                'avg_per_outflow' => $totalCount > 0 ? floatval($totalAmount / $totalCount) : 0,
            ];
        } catch (\Exception $e) {
            // En caso de error, retornar valores por defecto
            Log::error('Error en OutflowsDashboard getSummary: ' . $e->getMessage());
            return [
                'total_amount' => 0,
                'total_count' => 0,
                'avg_per_outflow' => 0,
            ];
        }
    }

    private function getInvestmentsTotal($season_id, $team_id)
    {
        try {
            // Obtener outflows que tienen operación "inversion" (case-insensitive)
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price
            $totalAmount = $outflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getInvestmentsTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getExpensesTotal($season_id, $team_id)
    {
        try {
            // Obtener outflows que tienen operación "gasto" (case-insensitive)
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%gasto%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price
            $totalAmount = $outflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getExpensesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getInvoicesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las facturas con sus productos
            $invoices = Invoice::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with('invoiceProducts')
                ->get();

            $totalCount = $invoices->count();

            // Calcular el total sumando unit_price × amount de cada producto
            $totalAmount = $invoices->sum(function($invoice) {
                return $invoice->invoiceProducts->sum(function($product) {
                    return $product->unit_price * $product->amount;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getInvoicesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getCreditNotesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las notas de crédito con sus items
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'credito')
                ->with('items')
                ->get();

            $totalCount = $notes->count();

            // Calcular el total sumando quantity × unit_price de cada item
            $totalAmount = $notes->sum(function($note) {
                return $note->items->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getCreditNotesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getDebitNotesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las notas de débito con sus items
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'debito')
                ->with('items')
                ->get();

            $totalCount = $notes->count();

            // Calcular el total sumando quantity × unit_price de cada item
            $totalAmount = $notes->sum(function($note) {
                return $note->items->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getDebitNotesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    /**
     * Obtiene el total de salidas agrupadas por Level1
     * 
     * Relaciones: Outflow -> invoiceProduct/creditDebitNoteItem -> product -> level1
     * Considera que algunos productos pueden tener level1_id NULL
     * 
     * @param int $season_id
     * @param int $team_id
     * @return array Arreglo con labels (nombres de level1) y data (totales)
     */
    private function getOutflowsByLevel1($season_id, $team_id)
    {
        try {
            // Obtener todos los outflows con sus relaciones anidadas, excluyendo inversiones
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereDoesntHave('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with([
                    'level3.level2.level1',
                    'invoiceProduct',
                    'creditDebitNoteItem'
                ])
                ->get();

            // Agrupar por level1 y calcular totales
            $groupedData = [];

            foreach ($outflows as $outflow) {
                $level1Name = null;
                $amount = 0;

                // Obtener el level1 desde la jerarquía outflow → level3 → level2 → level1
                if ($outflow->level3 && $outflow->level3->level2 && $outflow->level3->level2->level1) {
                    $level1Name = $outflow->level3->level2->level1->name;
                }

                // Calcular el monto según el origen (invoice o nota de crédito/débito)
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    $amount = $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                elseif ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    $amount = $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }

                // Si no tiene level1, agruparlo como "Sin Clasificar"
                $key = $level1Name ?? 'Sin Clasificar';

                // Acumular el total por level1
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = 0;
                }
                $groupedData[$key] += $amount;
            }

            // Ordenar por total descendente
            arsort($groupedData);

            // Convertir a formato para el gráfico
            return [
                'labels' => array_keys($groupedData),
                'data' => array_values($groupedData),
            ];

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getOutflowsByLevel1: ' . $e->getMessage());
            return [
                'labels' => [],
                'data' => [],
            ];
        }
    }

    /**
     * Obtiene el total de salidas agrupadas por Level2
     * 
     * Relaciones: Outflow -> level3 -> level2
     * Excluye inversiones para mostrar solo gastos operativos
     * 
     * @param int $season_id
     * @param int $team_id
     * @return array Arreglo con labels (nombres de level2) y data (totales)
     */
    private function getOutflowsByLevel2($season_id, $team_id)
    {
        try {
            // Obtener todos los outflows con sus relaciones anidadas, excluyendo inversiones
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereDoesntHave('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with([
                    'level3.level2.level1',
                    'invoiceProduct',
                    'creditDebitNoteItem'
                ])
                ->get();

            // Agrupar por level2 y calcular totales
            $groupedData = [];

            foreach ($outflows as $outflow) {
                $level2Name = null;
                $level1Name = null;
                $amount = 0;

                // Obtener el level2 y level1 desde la jerarquía outflow → level3 → level2 → level1
                if ($outflow->level3 && $outflow->level3->level2) {
                    $level2Name = $outflow->level3->level2->name;
                    if ($outflow->level3->level2->level1) {
                        $level1Name = $outflow->level3->level2->level1->name;
                    }
                }

                // Calcular el monto según el origen (invoice o nota de crédito/débito)
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    $amount = $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                elseif ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    $amount = $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }

                // Si no tiene level2, agruparlo como "Sin Clasificar"
                $key = $level2Name ?? 'Sin Clasificar';

                // Acumular el total por level2 y guardar level1
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'total' => 0,
                        'level1' => $level1Name ?? 'Sin Clasificar'
                    ];
                }
                $groupedData[$key]['total'] += $amount;
            }

            // Ordenar por total descendente
            uasort($groupedData, function($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            // Convertir a formato para el gráfico
            $labels = [];
            $data = [];
            $level1Data = [];
            
            foreach ($groupedData as $level2Name => $info) {
                $labels[] = $level2Name;
                $data[] = $info['total'];
                $level1Data[] = $info['level1'];
            }

            return [
                'labels' => $labels,
                'data' => $data,
                'level1' => $level1Data,
            ];

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getOutflowsByLevel2: ' . $e->getMessage());
            return [
                'labels' => [],
                'data' => [],
            ];
        }
    }

    private function getOutflowsByProject($season_id, $team_id)
    {
        try {
            // Obtener todos los outflows con sus relaciones necesarias
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with(['invoiceProduct', 'creditDebitNoteItem', 'project'])
                ->get();

            $groupedData = [];

            foreach ($outflows as $outflow) {
                $projectName = null;
                $amount = 0;

                // Obtener el nombre del proyecto
                if ($outflow->project_id && $outflow->project) {
                    $projectName = $outflow->project->name;
                }

                // Calcular el monto (cantidad × precio unitario)
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    $amount = $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                elseif ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    $amount = $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }

                // Si no tiene proyecto, agruparlo como "Sin Proyecto"
                $key = $projectName ?? 'Sin Proyecto';

                // Acumular el total por proyecto
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = 0;
                }
                $groupedData[$key] += $amount;
            }

            // Ordenar por total descendente
            arsort($groupedData);

            // Convertir a formato para el gráfico
            return [
                'labels' => array_keys($groupedData),
                'data' => array_values($groupedData),
            ];

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getOutflowsByProject: ' . $e->getMessage());
            return [
                'labels' => [],
                'data' => [],
            ];
        }
    }

    private function getTotalsByDevelopmentState($season_id, $team_id)
    {
        try {
            // Subconsulta para obtener superficie total por outflow
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            // Consulta principal para obtener totales por estado de desarrollo
            $results = DB::table('development_states')
                ->join('cost_centers', 'development_states.id', '=', 'cost_centers.development_state_id')
                ->join('outflow_cost_center', 'cost_centers.id', '=', 'outflow_cost_center.cost_center_id')
                ->join('outflows', function($join) use ($season_id, $team_id) {
                    $join->on('outflow_cost_center.outflow_id', '=', 'outflows.id')
                         ->where('outflows.season_id', '=', $season_id)
                         ->where('outflows.team_id', '=', $team_id);
                })
                ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', function($join) {
                    $join->on('outflows.id', '=', 'surface_totals.outflow_id');
                })
                ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
                ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
                ->selectRaw("
                    development_states.id,
                    development_states.name as state_name,
                    COALESCE(SUM(
                        CASE 
                            WHEN cost_centers.surface = 0 THEN 
                                outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                            ELSE 
                                (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) * 
                                COALESCE(invoice_product.unit_price, credit_debit_note_items.unit_price, 0)
                        END
                    ), 0) as total
                ")
                ->groupBy('development_states.id', 'development_states.name')
                ->orderBy('total', 'desc')
                ->get();

            // Formatear resultados
            return $results->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->state_name,
                    'total' => floatval($item->total ?? 0),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getTotalsByDevelopmentState: ' . $e->getMessage());
            return [];
        }
    }

    private function getTotalsByDevelopmentStateWithoutInvestments($season_id, $team_id)
    {
        try {
            // Subconsulta para obtener superficie total por outflow
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            // Consulta principal excluyendo inversiones
            $results = DB::table('development_states')
                ->join('cost_centers', 'development_states.id', '=', 'cost_centers.development_state_id')
                ->join('outflow_cost_center', 'cost_centers.id', '=', 'outflow_cost_center.cost_center_id')
                ->join('outflows', function($join) use ($season_id, $team_id) {
                    $join->on('outflow_cost_center.outflow_id', '=', 'outflows.id')
                         ->where('outflows.season_id', '=', $season_id)
                         ->where('outflows.team_id', '=', $team_id);
                })
                ->leftJoin('operations', 'outflows.operation_id', '=', 'operations.id')
                ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', function($join) {
                    $join->on('outflows.id', '=', 'surface_totals.outflow_id');
                })
                ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
                ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
                // Excluir inversiones
                ->where(function($query) {
                    $query->whereNull('operations.name')
                          ->orWhereRaw('LOWER(operations.name) NOT LIKE ?', ['%inversion%']);
                })
                ->selectRaw("
                    development_states.id,
                    development_states.name as state_name,
                    COALESCE(SUM(
                        CASE 
                            WHEN cost_centers.surface = 0 THEN 
                                outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                            ELSE 
                                (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) * 
                                COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                        END
                    ), 0) as total
                ")
                ->groupBy('development_states.id', 'development_states.name')
                ->orderBy('total', 'desc')
                ->get();

            // Formatear resultados
            return $results->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->state_name,
                    'total' => floatval($item->total ?? 0),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getTotalsByDevelopmentStateWithoutInvestments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula el costo kilo acumulado
     * Fórmula: Total de producción / Total de kilos estimados
     * 
     * @param int $season_id
     * @param int $team_id
     * @return array
     */
    private function getCostoKiloAcumulado($season_id, $team_id)
    {
        try {
            // 1. Obtener total de producción (outflows con operación "producción")
            $totalProduccion = $this->getTotalProduccion($season_id, $team_id);

            // 2. Obtener total de kilos estimados (última estimación)
            $totalEstimatedKilosData = $this->getTotalEstimatedKilos($season_id, $team_id);
            $kilosByFruit = $totalEstimatedKilosData['kilosByFruit'] ?? [];
            
            // Sumar todos los kilos de todas las frutas
            $totalKilos = array_sum($kilosByFruit);

            // 3. Calcular costo por kilo
            $costoKilo = 0;
            if ($totalKilos > 0) {
                $costoKilo = $totalProduccion / $totalKilos;
            }

            return [
                'totalProduccion' => floatval($totalProduccion),
                'totalKilos' => floatval($totalKilos),
                'costoKilo' => floatval($costoKilo),
            ];

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getCostoKiloAcumulado: ' . $e->getMessage());
            return [
                'totalProduccion' => 0,
                'totalKilos' => 0,
                'costoKilo' => 0,
            ];
        }
    }

    /**
     * Obtiene el total de gastos de producción
     * Replica la función getTotalsByDevelopmentStateWithoutInvestments pero filtrando por "producción"
     * 
     * @param int $season_id
     * @param int $team_id
     * @return float
     */
    private function getTotalProduccion($season_id, $team_id)
    {
        try {
            // Subconsulta para obtener superficie total por outflow
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            // Consulta principal filtrando por estado de desarrollo "producción" y operación "gasto"
            $result = DB::table('development_states')
                ->join('cost_centers', 'development_states.id', '=', 'cost_centers.development_state_id')
                ->join('outflow_cost_center', 'cost_centers.id', '=', 'outflow_cost_center.cost_center_id')
                ->join('outflows', function($join) use ($season_id, $team_id) {
                    $join->on('outflow_cost_center.outflow_id', '=', 'outflows.id')
                         ->where('outflows.season_id', '=', $season_id)
                         ->where('outflows.team_id', '=', $team_id);
                })
                ->join('operations', 'outflows.operation_id', '=', 'operations.id')
                ->leftJoinSub($surfaceTotalsSubquery, 'surface_totals', function($join) {
                    $join->on('outflows.id', '=', 'surface_totals.outflow_id');
                })
                ->leftJoin('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
                ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
                // Filtrar por estado de desarrollo "producción" (con o sin acento, mayúsculas/minúsculas)
                ->whereRaw("LOWER(REPLACE(development_states.name, 'ó', 'o')) LIKE ?", ['%produccion%'])
                // Filtrar por operación "gasto"
                ->whereRaw("LOWER(operations.name) = ?", ['gasto'])
                ->selectRaw("
                    COALESCE(SUM(
                        CASE 
                            WHEN cost_centers.surface = 0 THEN 
                                outflows.quantity * COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                            ELSE 
                                (cost_centers.surface * (outflows.quantity / NULLIF(surface_totals.total_surface, 0))) * 
                                COALESCE(invoice_products.unit_price, credit_debit_note_items.unit_price, 0)
                        END
                    ), 0) as total_produccion
                ")
                ->first();

            return floatval($result->total_produccion ?? 0);

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getTotalProduccion: ' . $e->getMessage());
            return 0;
        }
    }
}
