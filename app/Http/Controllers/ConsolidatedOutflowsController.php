<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Outflow;
use App\Models\Contract;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\ConsolidatedOutflowsExport;
use Maatwebsite\Excel\Facades\Excel;

class ConsolidatedOutflowsController extends Controller
{
    /**
     * Método index que delega a __invoke para compatibilidad de rutas
     */
    public function index(Request $request)
    {
        return $this->__invoke($request);
    }

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';
        $month = $request->month ?? '';
        $supplierId = $request->supplier_id ?? '';
        $level2Id = $request->level2_id ?? '';
        $level3Id = $request->level3_id ?? '';
        // Por defecto solo "Gestión" para evitar cargar Remuneraciones sin que el usuario lo pida explícitamente
        $tipoGasto = $request->has('tipo_gasto') ? $request->tipo_gasto : 'gestion';
        $sortBy = $request->sort_by ?? 'outflow_id';
        $sortDesc = filter_var($request->sort_desc ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) ($request->per_page ?? 50);

        // Eager loading optimizado: solo columnas necesarias
        $query = Outflow::with([
            'invoiceProduct:id,invoice_id,product_id,unit_price,branch_id',
            'invoiceProduct.product:id,name,unit_id',
            'invoiceProduct.product.unit:id,name',
            'invoiceProduct.branch:id,name',
            'invoiceProduct.invoice:id,supplier_id,number_document,type_document_id,company_reason_id',
            'invoiceProduct.invoice.supplier:id,name',
            'invoiceProduct.invoice.typeDocument:id,name',
            'invoiceProduct.invoice.companyReason:id,name',
            'creditDebitNoteItem:id,credit_debit_note_id,product_id,unit_price,branch_id',
            'creditDebitNoteItem.product:id,name,unit_id',
            'creditDebitNoteItem.product.unit:id,name',
            'creditDebitNoteItem.branch:id,name',
            'creditDebitNoteItem.creditDebitNote:id,supplier_id,number,invoice_id',
            'creditDebitNoteItem.creditDebitNote.supplier:id,name',
            'creditDebitNoteItem.creditDebitNote.invoice:id,company_reason_id',
            'creditDebitNoteItem.creditDebitNote.invoice.companyReason:id,name',
            'project:id,name',
            'operation:id,name',
            'machinery:id,cod_machinery,brand',
            'costCenters:id,outflow_id,cost_center_id,observations',
            'costCenters.costCenter:id,name,surface,development_state_id,branch_id,company_reason_id',
            'costCenters.costCenter.developmentState:id,name',
            'costCenters.costCenter.branch:id,name',
            'costCenters.costCenter.companyReason:id,name',
            'level3:id,name,level2_id',
            'level3.level2:id,name,level1_id',
            'level3.level2.level1:id,name',
        ])
        ->select(['id', 'invoice_product_id', 'credit_debit_note_item_id', 'project_id',
                   'operation_id', 'machinery_id', 'quantity', 'notes', 'date',
                   'team_id', 'season_id', 'level3_id'])
        ->where('team_id', $user->team_id)
        ->where('season_id', $season_id);

        if ($month) {
            $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month]);
        }

        if ($supplierId) {
            $query->where(function($q) use ($supplierId) {
                $q->whereHas('invoiceProduct.invoice.supplier', function($subQ) use ($supplierId) {
                    $subQ->where('suppliers.id', $supplierId);
                })->orWhereHas('creditDebitNoteItem.creditDebitNote.supplier', function($subQ) use ($supplierId) {
                    $subQ->where('suppliers.id', $supplierId);
                });
            });
        }

        if ($level2Id) {
            $query->whereHas('level3.level2', function($subQ) use ($level2Id) {
                $subQ->where('level2s.id', $level2Id);
            });
        }

        if ($level3Id) {
            $query->whereHas('level3', function($subQ) use ($level3Id) {
                $subQ->where('level3s.id', $level3Id);
            });
        }

        // Búsqueda server-side: busca en TODOS los registros de la BD
        if ($term) {
            $query->where(function($q) use ($term) {
                $q->whereHas('invoiceProduct.product', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('creditDebitNoteItem.product', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('invoiceProduct.invoice.supplier', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('creditDebitNoteItem.creditDebitNote.supplier', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('project', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('costCenters.costCenter', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('level3', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('level3.level2', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                })
                ->orWhereHas('level3.level2.level1', function($subQ) use ($term) {
                    $subQ->where('name', 'like', '%' . $term . '%');
                });
            });
        }

        $baseOutflows = Outflow::select(['id', 'date', 'invoice_product_id', 'credit_debit_note_item_id', 'level3_id'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->with([
                'invoiceProduct.invoice.supplier:id,name',
                'creditDebitNoteItem.creditDebitNote.supplier:id,name',
                'level3.level2:id,name',
            ])
            ->get();

        $monthOptions = $baseOutflows
            ->filter(fn($outflow) => !empty($outflow->date))
            ->map(fn($outflow) => \Carbon\Carbon::parse($outflow->date)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn($value) => ['value' => $value, 'label' => \Carbon\Carbon::createFromFormat('Y-m', $value)->locale('es')->translatedFormat('Y-m')]);

        // Nota: las opciones usan el ID real de la relación ya cargada (no se re-busca por
        // nombre), porque nombres iguales pueden repetirse entre equipos distintos y eso
        // traía el ID de otro equipo, dejando el filtro sin resultados.
        $supplierOptions = $baseOutflows
            ->map(function($outflow) {
                $supplier = $outflow->invoice_product_id
                    ? $outflow->invoiceProduct?->invoice?->supplier
                    : $outflow->creditDebitNoteItem?->creditDebitNote?->supplier;

                return $supplier ? ['value' => $supplier->id, 'label' => $supplier->name] : null;
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();

        $level2Options = $baseOutflows
            ->map(function($outflow) {
                $level2 = $outflow->level3?->level2;
                return $level2 ? ['value' => $level2->id, 'label' => $level2->name] : null;
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();

        if ($level2Id) {
            $baseOutflows = $baseOutflows->filter(function($outflow) use ($level2Id) {
                return (int) optional($outflow->level3?->level2)->id === (int) $level2Id;
            });
        }

        $level3Options = $baseOutflows
            ->map(function($outflow) {
                $level3 = $outflow->level3;
                return $level3 ? ['value' => $level3->id, 'label' => $level3->name] : null;
            })
            ->filter()
            ->unique('value')
            ->sortBy('label')
            ->values();

        // Si el filtro pide "solo remuneraciones", no es necesario traer los outflows de gestión
        $outflows = ($tipoGasto !== 'remuneraciones') ? $query->orderBy('id', 'desc')->get() : collect();

        // Expandir cada outflow por sus centros de costo
        $expandedData = [];

        foreach ($outflows as $outflow) {
            $isInvoice = !is_null($outflow->invoice_product_id);

            $commonData = [
                'outflow_id' => $outflow->id,
                'tipo_gasto' => 'Gestión',
                'date' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->format('d-m-Y') : null,
                'month' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->locale('es')->translatedFormat('F') : null,
                'supplier' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->supplier->name ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->supplier->name ?? '-'),
                'number_document' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->number_document ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->number ?? '-'),
                'tipo_documento' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->typeDocument->name ?? 'Factura')
                    : 'Nota Débito',
                'product_name' => $isInvoice
                    ? ($outflow->invoiceProduct->product->name ?? '-')
                    : ($outflow->creditDebitNoteItem->product->name ?? '-'),
                'unit_name' => $isInvoice
                    ? ($outflow->invoiceProduct->product->unit->name ?? '-')
                    : ($outflow->creditDebitNoteItem->product->unit->name ?? '-'),
                'quantity_total' => $outflow->quantity,
                'unit_price' => $isInvoice
                    ? ($outflow->invoiceProduct->unit_price ?? 0)
                    : ($outflow->creditDebitNoteItem->unit_price ?? 0),
                'project' => $outflow->project->name ?? null,
                'operation' => $outflow->operation->name ?? null,
                'machinery' => $outflow->machinery ? trim($outflow->machinery->cod_machinery . ' - ' . $outflow->machinery->brand) : null,
                'notes' => $outflow->notes,
                'level1_name' => $outflow->level3->level2->level1->name ?? null,
                'level2_name' => $outflow->level3->level2->name ?? null,
                'level3_name' => $outflow->level3->name ?? null,
                'branch_factura' => $isInvoice
                    ? ($outflow->invoiceProduct->branch->name ?? '-')
                    : ($outflow->creditDebitNoteItem->branch->name ?? '-'),
                'company_reason_factura' => $isInvoice
                    ? ($outflow->invoiceProduct->invoice->companyReason->name ?? '-')
                    : ($outflow->creditDebitNoteItem->creditDebitNote->invoice->companyReason->name ?? '-'),
            ];

            $totalSuperficie = $outflow->costCenters->sum(function($occ) {
                return $occ->costCenter->surface ?? 0;
            });

            $cantidadPorHa = $totalSuperficie > 0
                ? $outflow->quantity / $totalSuperficie
                : 0;

            if ($outflow->costCenters->isEmpty()) {
                $expandedData[] = array_merge($commonData, [
                    'cost_center_id' => null,
                    'cost_center_name' => '-',
                    'branch_cc' => '-',
                    'company_reason_cc' => '-',
                    'surface' => 0,
                    'cantidad_asignada' => $outflow->quantity,
                    'development_state' => null,
                    'total_superficie' => 0,
                    'cantidad_por_ha' => 0,
                    'total' => $outflow->quantity * ($commonData['unit_price']),
                ]);
                continue;
            }

            foreach ($outflow->costCenters as $occ) {
                $superficie = $occ->costCenter->surface ?? 0;

                if ($superficie == 0) {
                    $cantidadAsignada = $outflow->quantity;
                    $totalCalculado = $outflow->quantity * $commonData['unit_price'];
                } else {
                    $cantidadAsignada = $superficie * $cantidadPorHa;
                    $totalCalculado = $cantidadAsignada * $commonData['unit_price'];
                }

                $expandedData[] = array_merge($commonData, [
                    'cost_center_id' => $occ->costCenter->id,
                    'cost_center_name' => $occ->costCenter->name,
                    'branch_cc' => $occ->costCenter->branch->name ?? '-',
                    'company_reason_cc' => $occ->costCenter->companyReason->name ?? '-',
                    'surface' => $superficie,
                    'cantidad_asignada' => round($cantidadAsignada, 2),
                    'development_state' => $occ->costCenter->developmentState->name ?? null,
                    'total_superficie' => $totalSuperficie,
                    'cantidad_por_ha' => round($cantidadPorHa, 4),
                    'total' => round($totalCalculado, 2),
                    'cc_observations' => $occ->observations,
                ]);
            }
        }

        // Remuneraciones (Labores por Centro de Costo): solo se consulta cuando el filtro lo requiere.
        // No aplica si hay un filtro de proveedor activo (las remuneraciones no tienen proveedor).
        if ($tipoGasto !== 'gestion' && !$supplierId) {
            $remuneracionesRows = $this->buildRemuneracionesRows($user->team_id, $season_id);

            if ($month) {
                $monthNum = (int) substr($month, 5, 2);
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => $r['month_id'] === $monthNum));
            }
            if ($level2Id) {
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => (string) $r['level2_id'] === (string) $level2Id));
            }
            if ($level3Id) {
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, fn($r) => (string) $r['level3_id'] === (string) $level3Id));
            }
            if ($term) {
                $termLower = mb_strtolower($term);
                $remuneracionesRows = array_values(array_filter($remuneracionesRows, function($r) use ($termLower) {
                    $haystack = mb_strtolower(($r['product_name'] ?? '') . ' ' . ($r['level1_name'] ?? '') . ' ' . ($r['level2_name'] ?? '') . ' ' . ($r['level3_name'] ?? '') . ' ' . ($r['cost_center_name'] ?? ''));
                    return str_contains($haystack, $termLower);
                }));
            }

            $expandedData = array_merge($expandedData, $remuneracionesRows);
        }

        // Totales globales (sobre TODOS los datos filtrados, antes de paginar)
        $totalGeneral = array_sum(array_column($expandedData, 'total'));
        $totalCount = count($expandedData);

        // Ordenamiento server-side
        $numericFields = ['outflow_id', 'quantity_total', 'surface', 'cantidad_asignada', 'unit_price', 'total', 'cantidad_por_ha', 'total_superficie'];
        usort($expandedData, function($a, $b) use ($sortBy, $sortDesc, $numericFields) {
            $aVal = $a[$sortBy] ?? '';
            $bVal = $b[$sortBy] ?? '';

            if (in_array($sortBy, $numericFields)) {
                $aVal = (float) $aVal;
                $bVal = (float) $bVal;
            } else {
                $aVal = strtolower((string) $aVal);
                $bVal = strtolower((string) $bVal);
            }

            $result = $aVal <=> $bVal;
            return $sortDesc ? -$result : $result;
        });

        // Paginación server-side (después de búsqueda y ordenamiento)
        $page = max(1, (int) $request->input('page', 1));
        $paginatedItems = array_slice($expandedData, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            array_values($paginatedItems),
            $totalCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('ConsolidatedOutflows', [
            'outflows' => $paginator,
            'filters' => [
                'term' => $term,
                'month' => $month,
                'supplier_id' => $supplierId,
                'level2_id' => $level2Id,
                'level3_id' => $level3Id,
                'tipo_gasto' => $tipoGasto,
                'sort_by' => $sortBy,
                'sort_desc' => $sortDesc,
                'per_page' => $perPage,
            ],
            'filterOptions' => [
                'months' => $monthOptions,
                'suppliers' => $supplierOptions,
                'levels2' => $level2Options,
                'levels3' => $level3Options,
            ],
            'totals' => [
                'total_general' => round($totalGeneral, 0),
                'total_count' => $totalCount,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $term = $request->term ?? '';
        $month = $request->month ?? '';
        $supplierId = $request->supplier_id ?? '';
        $level2Id = $request->level2_id ?? '';
        $level3Id = $request->level3_id ?? '';
        $tipoGasto = $request->tipo_gasto ?? '';

        return Excel::download(
            new ConsolidatedOutflowsExport($term, $month, $supplierId, $level2Id, $level3Id, $tipoGasto),
            'consolidado_salidas.xlsx'
        );
    }

    /**
     * Construye las filas de "Remuneraciones" (jornadas, bonos mensuales y horas extra)
     * agrupadas por Centro de Costo, con la misma forma que las filas de Outflows (Gestión),
     * para poder mezclarlas en la misma tabla de Consolidado de Salidas.
     *
     * Siempre trae la temporada COMPLETA (todos los meses); el filtro de mes se aplica
     * después, en PHP, comparando solo el número de mes (month_id es el mes calendario 1-12).
     */
    private function buildRemuneracionesRows($teamId, $seasonId): array
    {
        $contractIds = Contract::where('team_id', $teamId)->pluck('id');

        $yields = DB::table('daily_yields as dy')
            ->join('labor_types as lt', 'dy.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('dy.team_id', $teamId)
            ->where('dy.season_id', $seasonId)
            ->select(
                'dy.id',
                DB::raw('MONTH(dy.date) as month_id'),
                'dy.amount', 'dy.bonus_amount', 'dy.target_price_bonus', 'dy.workdays',
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $yieldIds = $yields->pluck('id');
        $yieldCCs = $yieldIds->isEmpty() ? collect() : DB::table('daily_yield_cost_center as dycc')
            ->join('cost_centers as cc', 'dycc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('dycc.daily_yield_id', $yieldIds)
            ->select('dycc.daily_yield_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('daily_yield_id');

        $bonuses = DB::table('monthly_bonuses as mb')
            ->join('labor_types as lt', 'mb.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('mb.team_id', $teamId)
            ->where('mb.season_id', $seasonId)
            ->whereIn('mb.contract_id', $contractIds)
            ->select(
                'mb.id', 'mb.month_id', 'mb.amount',
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $bonusIds = $bonuses->pluck('id');
        $bonusCCs = $bonusIds->isEmpty() ? collect() : DB::table('monthly_bonus_cost_centers as mbcc')
            ->join('cost_centers as cc', 'mbcc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('mbcc.monthly_bonus_id', $bonusIds)
            ->select('mbcc.monthly_bonus_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('monthly_bonus_id');

        $overtimes = DB::table('overtime_hours as oh')
            ->join('labor_types as lt', 'oh.labor_type_id', '=', 'lt.id')
            ->leftJoin('level3s as l3', 'lt.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('oh.team_id', $teamId)
            ->where('oh.season_id', $seasonId)
            ->whereIn('oh.contract_id', $contractIds)
            ->select(
                'oh.id', 'oh.month_id',
                DB::raw('ROUND(oh.hours * oh.base_salary_snapshot * oh.hourly_rate_factor_snapshot * oh.overtime_multiplier_snapshot) as amount'),
                'l3.id as level3_id', 'l3.name as level3_name',
                'l2.id as level2_id', 'l2.name as level2_name',
                'l1.name as level1_name'
            )
            ->get();

        $overtimeIds = $overtimes->pluck('id');
        $overtimeCCs = $overtimeIds->isEmpty() ? collect() : DB::table('overtime_hour_cost_centers as ohcc')
            ->join('cost_centers as cc', 'ohcc.cost_center_id', '=', 'cc.id')
            ->leftJoin('branches as br', 'cc.branch_id', '=', 'br.id')
            ->leftJoin('company_reasons as cr', 'cc.company_reason_id', '=', 'cr.id')
            ->whereIn('ohcc.overtime_hour_id', $overtimeIds)
            ->select('ohcc.overtime_hour_id', 'cc.id as cost_center_id', 'cc.name as cc_name', 'cc.surface',
                DB::raw("COALESCE(br.name, '-') as branch_name"), DB::raw("COALESCE(cr.name, '-') as company_reason_name"))
            ->get()
            ->groupBy('overtime_hour_id');

        // Agregamos por (mes + centro de costo + labor) en vez de emitir una fila por cada
        // registro crudo: una tarja puede repartirse entre decenas de CC, lo que generaba
        // decenas de miles de filas y agotaba la memoria disponible.
        $bucket = [];

        $addToBucket = function ($monthId, $ccId, $ccName, $branchName, $crName, $surface, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays, $amount) use (&$bucket) {
            $key = $monthId . '|' . ($ccId ?: 0) . '|' . ($level3Id ?: 0);
            if (!isset($bucket[$key])) {
                $bucket[$key] = [
                    'month_id' => $monthId,
                    'cost_center_id' => $ccId ?: null,
                    'cost_center_name' => $ccName,
                    'branch_cc' => $branchName,
                    'company_reason_cc' => $crName,
                    'surface' => $surface,
                    'level1_name' => $level1,
                    'level2_name' => $level2Name,
                    'level2_id' => $level2Id,
                    'level3_name' => $level3Name,
                    'level3_id' => $level3Id,
                    'workdays' => 0.0,
                    'amount' => 0.0,
                ];
            }
            $bucket[$key]['workdays'] += $workdays;
            $bucket[$key]['amount'] += $amount;
        };

        $processRecord = function ($record, $ccGrouped, string $source) use (&$addToBucket) {
            $monthId = (int) $record->month_id;
            $level1 = $record->level1_name ?? null;
            $level2Name = $record->level2_name ?? null;
            $level2Id = $record->level2_id ?? null;
            $level3Name = $record->level3_name ?? null;
            $level3Id = $record->level3_id ?? null;
            $workdays = (float) ($record->workdays ?? 0);
            $amount = $source === 'yield'
                ? (float) (($record->amount ?? 0) + ($record->bonus_amount ?? 0) + ($record->target_price_bonus ?? 0))
                : (float) ($record->amount ?? 0);

            $ccs = $ccGrouped->get($record->id, collect());
            $totalSurf = $ccs->sum('surface');
            $nCCs = count($ccs);

            if ($nCCs === 0) {
                $addToBucket($monthId, 0, '-', '-', '-', 0, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays, $amount);
                return;
            }

            foreach ($ccs as $cc) {
                $surf = (float) ($cc->surface ?? 0);
                $prop = $totalSurf > 0 ? $surf / $totalSurf : 1 / $nCCs;
                $addToBucket($monthId, $cc->cost_center_id, $cc->cc_name ?? '-', $cc->branch_name ?? '-', $cc->company_reason_name ?? '-', $surf, $level1, $level2Name, $level2Id, $level3Name, $level3Id, $workdays * $prop, $amount * $prop);
            }
        };

        foreach ($yields as $y) { $processRecord($y, $yieldCCs, 'yield'); }
        foreach ($bonuses as $b) { $processRecord($b, $bonusCCs, 'bonus'); }
        foreach ($overtimes as $o) { $processRecord($o, $overtimeCCs, 'overtime'); }

        $rows = [];
        foreach ($bucket as $key => $b) {
            $monthName = \Carbon\Carbon::createFromDate(2000, max(1, min(12, $b['month_id'] ?: 1)), 1)->locale('es')->translatedFormat('F');
            $rows[] = [
                'outflow_id' => 'R-' . $key,
                'tipo_gasto' => 'Remuneraciones',
                'date' => null,
                'month' => $monthName,
                'month_id' => $b['month_id'],
                'supplier' => '-',
                'number_document' => '-',
                'tipo_documento' => '-',
                'product_name' => $b['level3_name'] ?? 'Sin Labor',
                'unit_name' => 'Jornadas',
                'quantity_total' => round($b['workdays'], 2),
                'unit_price' => 0,
                'project' => null,
                'operation' => null,
                'machinery' => null,
                'notes' => null,
                'level1_name' => $b['level1_name'],
                'level2_name' => $b['level2_name'],
                'level2_id' => $b['level2_id'],
                'level3_name' => $b['level3_name'],
                'level3_id' => $b['level3_id'],
                'branch_factura' => '-',
                'company_reason_factura' => '-',
                'cost_center_id' => $b['cost_center_id'],
                'cost_center_name' => $b['cost_center_name'],
                'branch_cc' => $b['branch_cc'],
                'company_reason_cc' => $b['company_reason_cc'],
                'surface' => $b['surface'],
                'cantidad_asignada' => round($b['workdays'], 2),
                'development_state' => null,
                'total_superficie' => $b['surface'],
                'cantidad_por_ha' => 0,
                'total' => round($b['amount'], 2),
            ];
        }

        return $rows;
    }
}
