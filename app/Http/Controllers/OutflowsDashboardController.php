<?php

namespace App\Http\Controllers;

use App\Models\CompanyReason;
use App\Models\CostCenter;
use App\Models\CreditDebitNote;
use App\Models\Invoice;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Controllers\Traits\BudgetTotalsTrait;
use App\Http\Controllers\Traits\PayrollDataTrait;

class OutflowsDashboardController extends Controller
{
    use BudgetTotalsTrait, PayrollDataTrait;
    public function index(Request $request)
    {
        $season_id = session('season_id');
        $user = Auth::user();
        $team_id = $user->team_id;

        // Validar que exista season_id en sesión
        // Nota: La ruta 'select.budget' es donde el usuario selecciona la temporada (season)
        // El nombre es histórico y se mantiene por compatibilidad
        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        $company_reason_ids = collect($request->input('company_reason_ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();
        $company_reason_id = count($company_reason_ids) > 0 ? $company_reason_ids : null;

        // Obtener dollar_price del admin del equipo
        $adminUser = \App\Models\User::where('team_id', $team_id)
            ->role('Admin')
            ->first();
        $dollarPrice = $adminUser?->dollar_price ?? 970;

        return Inertia::render('OutflowsDashboard', [
            'dollarPrice'           => $dollarPrice,
            'isAdmin'               => $user->hasRole('Admin'),
            'companyReasons'        => $this->getCompanyReasons($season_id, $team_id),
            'activeCompanyReasonIds' => $company_reason_ids,
            'summary'               => $this->getSummary($season_id, $team_id, $company_reason_id),
            'investments'           => $this->getInvestmentsTotal($season_id, $team_id, $company_reason_id),
            'expenses'              => $this->getExpensesTotal($season_id, $team_id, $company_reason_id),
            'invoices'              => $this->getInvoicesTotal($season_id, $team_id, $company_reason_id),
            'creditNotes'           => $this->getCreditNotesTotal($season_id, $team_id, $company_reason_id),
            'debitNotes'            => $this->getDebitNotesTotal($season_id, $team_id, $company_reason_id),
            'byLevel1'              => $this->getOutflowsByLevel1($season_id, $team_id, $company_reason_id),
            'byLevel2'              => $this->mergePayrollIntoLevel2(
                $this->getOutflowsByLevel2($season_id, $team_id, $company_reason_id),
                $this->getPayrollByLevel2($team_id, $season_id, $company_reason_id)
            ),
            'byProject'             => $this->getOutflowsByProject($season_id, $team_id, $company_reason_id),
            'byDevelopmentState'    => $this->getTotalsByDevelopmentState($season_id, $team_id, $company_reason_id),
            'byDevelopmentStateWithoutInvestments' => $this->getTotalsByDevelopmentStateWithoutInvestments($season_id, $team_id, $company_reason_id),
            'costoKiloAcumulado'    => $this->getCostoKiloAcumulado($season_id, $team_id, $company_reason_id),
            'payrollSummary'        => $this->getPayrollSummary($team_id, $season_id, $company_reason_id),
            'payrollByDevState'     => $this->getPayrollByDevelopmentState($team_id, $season_id, $company_reason_id),
        ]);
    }

    /**
     * Retorna las razones sociales disponibles para la temporada y equipo dados.
     * Combina las que aparecen en facturas y en centros de costo.
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
     * Aplica el filtro de razón social a un query Eloquent de Outflow.
     * Los outflows llegan a company_reason a través de su factura o nota.
     */
    private function withCompanyReasonFilter($query, $company_reason_id)
    {
        if (!$company_reason_id) return $query;
        $ids = is_array($company_reason_id) ? $company_reason_id : [$company_reason_id];
        return $query->where(function ($w) use ($ids) {
            $w->whereHas('invoiceProduct.invoice', fn($q) => $q->whereIn('company_reason_id', $ids))
              ->orWhereHas('creditDebitNoteItem.creditDebitNote.invoice', fn($q) => $q->whereIn('company_reason_id', $ids));
        });
    }

    /**
     * Agrega JOINs y WHERE de razón social a un DB query builder que ya tiene
     * invoice_products y credit_debit_note_items unidos.
     */
    private function addCompanyReasonJoin($query, $company_reason_id)
    {
        if (!$company_reason_id) return $query;
        $ids = is_array($company_reason_id) ? $company_reason_id : [$company_reason_id];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $query
            ->leftJoin('invoices as inv_cr', 'invoice_products.invoice_id', '=', 'inv_cr.id')
            ->leftJoin('credit_debit_notes as cdn_cr', 'credit_debit_note_items.credit_debit_note_id', '=', 'cdn_cr.id')
            ->leftJoin('invoices as inv_cdn_cr', 'cdn_cr.invoice_id', '=', 'inv_cdn_cr.id')
            ->whereRaw("COALESCE(inv_cr.company_reason_id, inv_cdn_cr.company_reason_id) IN ({$placeholders})", $ids);
    }

    /**
     * Agrega los montos de remuneraciones (por level2) al array de byLevel2.
     * Si el level2 ya existe, suma. Si no existe, lo agrega al final.
     * Luego re-ordena por monto descendente.
     */
    private function mergePayrollIntoLevel2(array $level2Data, array $payrollByLevel2): array
    {
        if (empty($payrollByLevel2)) return $level2Data;

        $indexMap = array_flip($level2Data['labels']);

        foreach ($payrollByLevel2 as $name => $info) {
            if (isset($indexMap[$name])) {
                $i = $indexMap[$name];
                $level2Data['data'][$i] += (int) round($info['total']);
            } else {
                $level2Data['labels'][] = $name;
                $level2Data['data'][]   = (int) round($info['total']);
                $level2Data['level1'][] = $info['level1'];
            }
        }

        // Re-ordenar por monto descendente
        $combined = array_map(null, $level2Data['labels'], $level2Data['data'], $level2Data['level1']);
        usort($combined, fn($a, $b) => $b[1] <=> $a[1]);
        $level2Data['labels'] = array_column($combined, 0);
        $level2Data['data']   = array_column($combined, 1);
        $level2Data['level1'] = array_column($combined, 2);

        return $level2Data;
    }

    private function getSummary($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->with(['invoiceProduct', 'creditDebitNoteItem']),
                $company_reason_id
            )->get();

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

    private function getInvestmentsTotal($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->whereHas('operation', function($query) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                    })
                    ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation']),
                $company_reason_id
            )->get();

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

    private function getExpensesTotal($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->whereHas('operation', function($query) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%gasto%']);
                    })
                    ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation']),
                $company_reason_id
            )->get();

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

    private function getInvoicesTotal($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $invoices = Invoice::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->when($company_reason_id, fn($q) => $q->whereIn('company_reason_id', $company_reason_id))
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

    private function getCreditNotesTotal($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'credito')
                ->where('affects_inventory', 1)
                ->when($company_reason_id, fn($q) => $q->whereHas('invoice', fn($iq) => $iq->whereIn('company_reason_id', $company_reason_id)))
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

    private function getDebitNotesTotal($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'debito')
                ->where('affects_inventory', 1)
                ->when($company_reason_id, fn($q) => $q->whereHas('invoice', fn($iq) => $iq->whereIn('company_reason_id', $company_reason_id)))
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
    private function getOutflowsByLevel1($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->whereDoesntHave('operation', function($query) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                    })
                    ->with([
                        'level3.level2.level1',
                        'invoiceProduct',
                        'creditDebitNoteItem'
                    ]),
                $company_reason_id
            )->get();

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
    private function getOutflowsByLevel2($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->whereDoesntHave('operation', function($query) {
                        $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                    })
                    ->with([
                        'level3.level2.level1',
                        'invoiceProduct',
                        'creditDebitNoteItem'
                    ]),
                $company_reason_id
            )->get();

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

    private function getOutflowsByProject($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $outflows = $this->withCompanyReasonFilter(
                Outflow::where('season_id', $season_id)
                    ->where('team_id', $team_id)
                    ->with(['invoiceProduct', 'creditDebitNoteItem', 'project']),
                $company_reason_id
            )->get();

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

    private function getTotalsByDevelopmentState($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            $query = DB::table('development_states')
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
                ->leftJoin('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id');

            $query = $this->addCompanyReasonJoin($query, $company_reason_id);

            $results = $query->selectRaw("
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
            Log::error('Error en OutflowsDashboard getTotalsByDevelopmentState: ' . $e->getMessage());
            return [];
        }
    }

    private function getTotalsByDevelopmentStateWithoutInvestments($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            $query = DB::table('development_states')
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
                ->where(function($q) {
                    $q->whereNull('operations.name')
                      ->orWhereRaw('LOWER(operations.name) NOT LIKE ?', ['%inversion%']);
                });

            $query = $this->addCompanyReasonJoin($query, $company_reason_id);

            $results = $query->selectRaw("
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

            // Separar admin de no-admin
            $adminTotal = 0;
            $adminId = null;
            $adminName = null;
            $nonAdmin = [];
            foreach ($results as $item) {
                $norm = mb_strtolower(str_replace(['ó','Ó'], ['o','O'], $item->state_name));
                if (str_contains($norm, 'administracion')) {
                    $adminTotal += floatval($item->total ?? 0);
                    $adminId = $item->id;
                    $adminName = $item->state_name;
                } else {
                    $nonAdmin[] = $item;
                }
            }

            // Obtener superficie por estado de desarrollo (para prorrateo de admin)
            $surfaces = DB::table('cost_centers')
                ->where('season_id', $season_id)
                ->whereNotNull('development_state_id')
                ->where('surface', '>', 0)
                ->select('development_state_id', DB::raw('SUM(surface) as total_surface'))
                ->groupBy('development_state_id')
                ->pluck('total_surface', 'development_state_id');

            $totalSurface = $surfaces->sum();

            // Prorratear admin en cada estado proporcionalmente a sus hectáreas
            $formatted = collect($nonAdmin)->map(function($item) use ($adminTotal, $surfaces, $totalSurface) {
                $stateSurface = floatval($surfaces[$item->id] ?? 0);
                $adminShare = ($totalSurface > 0 && $adminTotal > 0)
                    ? round($adminTotal * ($stateSurface / $totalSurface), 2)
                    : 0;

                return [
                    'id' => $item->id,
                    'name' => $item->state_name,
                    'total' => floatval($item->total ?? 0),
                    'admin_share' => $adminShare,
                ];
            })->sortByDesc('total')->values()->toArray();

            // Incluir admin como ítem separado para la lista visual
            if ($adminTotal > 0 && $adminId !== null) {
                $formatted[] = [
                    'id' => $adminId,
                    'name' => $adminName,
                    'total' => $adminTotal,
                    'admin_share' => 0,
                ];
            }

            return $formatted;

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
    private function getCostoKiloAcumulado($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $totalProduccion = $this->getTotalProduccion($season_id, $team_id, $company_reason_id);

            $totalEstimatedKilosData = $this->getTotalEstimatedKilos($season_id, $team_id, $company_reason_id);
            $kilosByEstimate = $totalEstimatedKilosData['kilosByEstimate'] ?? [];
            $defaultStatusId = $totalEstimatedKilosData['defaultEstimateStatusId'] ?? null;
            $kilosByFruit = ($defaultStatusId && isset($kilosByEstimate[$defaultStatusId]))
                ? $kilosByEstimate[$defaultStatusId]
                : [];
            
            // Sumar todos los kilos de todas las frutas
            $totalKilos = array_sum($kilosByFruit);

            // 3. Calcular costo por kilo
            $costoKilo = 0;
            if ($totalKilos > 0) {
                $costoKilo = $totalProduccion / $totalKilos;
            }

            return [
                'totalProduccion' => floatval($totalProduccion),
                'totalKilos'      => floatval($totalKilos),
                'costoKilo'       => floatval($costoKilo),
                'kilosByEstimate' => $kilosByEstimate,
                'estimateOptions' => $totalEstimatedKilosData['estimateOptions'] ?? [],
                'fruitNames'      => $totalEstimatedKilosData['fruitNames'] ?? [],
                'defaultEstimateStatusId' => $defaultStatusId,
            ];

        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getCostoKiloAcumulado: ' . $e->getMessage());
            return [
                'totalProduccion' => 0,
                'totalKilos'      => 0,
                'costoKilo'       => 0,
            ];
        }
    }

    private function getTotalProduccion($season_id, $team_id, $company_reason_id = null)
    {
        try {
            $surfaceTotalsSubquery = DB::table('outflow_cost_center')
                ->join('cost_centers', 'outflow_cost_center.cost_center_id', '=', 'cost_centers.id')
                ->select('outflow_cost_center.outflow_id', DB::raw('SUM(cost_centers.surface) as total_surface'))
                ->groupBy('outflow_cost_center.outflow_id');

            $query = DB::table('development_states')
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
                ->whereRaw("LOWER(REPLACE(development_states.name, 'ó', 'o')) LIKE ?", ['%produccion%'])
                ->whereRaw("LOWER(operations.name) = ?", ['gasto']);

            $query = $this->addCompanyReasonJoin($query, $company_reason_id);

            $result = $query->selectRaw("
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
