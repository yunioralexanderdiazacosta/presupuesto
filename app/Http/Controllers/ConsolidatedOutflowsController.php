<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Outflow;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
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

        $outflows = $query->orderBy('id', 'desc')->get();

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

        return Excel::download(
            new ConsolidatedOutflowsExport($term, $month, $supplierId, $level2Id, $level3Id),
            'consolidado_salidas.xlsx'
        );
    }
}
