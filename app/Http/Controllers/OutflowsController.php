<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use App\Models\Invoice;
use App\Models\Branch;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use \App\Traits\HasInventory;

class OutflowsController extends Controller
{
    use HasInventory;

    public function __invoke(Request $request)
    {
        // Obtener usuario y temporada
        $user = Auth::user();
        $season_id = session('season_id');
        $term = $request->term ?? '';

        // Precalcular devoluciones por invoice_product_id en notas de crédito (solo que afectan inventario)
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');

        // Traer productos de facturas
        $invoices = Invoice::with(['supplier', 'typeDocument', 'invoiceProducts.product.unit', 'invoiceProducts.branch:id,name'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get();

        // Precalcular salidas por invoice_product_id
        $outflowsByInvoiceProduct = DB::table('outflows')
            ->select('invoice_product_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('invoice_product_id')
            ->groupBy('invoice_product_id')
            ->pluck('total_consumido', 'invoice_product_id');

        // Precalcular salidas por credit_debit_note_item_id
        $outflowsByDebitNoteItem = DB::table('outflows')
            ->select('credit_debit_note_item_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('credit_debit_note_item_id')
            ->groupBy('credit_debit_note_item_id')
            ->pluck('total_consumido', 'credit_debit_note_item_id');

        // Precalcular NC financieras (affects_inventory=0) por invoice_product
        $financialNCsByIP = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 0)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity * credit_debit_note_items.unit_price) as nc_total'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('nc_total', 'credit_debit_note_items.invoice_product_id');

        $rows = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                if ($term && stripos($invoice->number_document, $term) === false) {
                    continue;
                }
                $consumido = $outflowsByInvoiceProduct[$invoiceProduct->id] ?? 0;
                $devuelto = $creditNotesReturns[$invoiceProduct->id] ?? 0;
                $cantidadOriginal = $invoiceProduct->quantity ?? $invoiceProduct->amount ?? 0;
                $stockLinea = round($cantidadOriginal - $consumido - $devuelto, 2);

                // Excluir líneas con stock cero o negativo
                if ($stockLinea <= 0) {
                    continue;
                }
                // Buscar info de nota(s) de crédito asociada(s)
                $creditNoteInfo = null;
                if ($devuelto > 0) {
                    $creditNotes = \App\Models\CreditDebitNote::where('type', 'credito')
                        ->whereHas('items', function($q) use ($invoiceProduct) {
                            $q->where('invoice_product_id', $invoiceProduct->id);
                        })
                        ->with(['supplier', 'items' => function($q) use ($invoiceProduct) {
                            $q->where('invoice_product_id', $invoiceProduct->id);
                        }, 'items.product'])
                        ->get();
                    $creditNoteInfo = $creditNotes->map(function($note) {
                        return [
                            'number' => $note->number,
                            'supplier' => $note->supplier->name ?? '',
                            'date' => $note->date?->format('Y-m-d'),
                            'items' => $note->items->map(function($item) {
                                return [
                                    'product' => $item->product->name ?? '',
                                    'quantity' => $item->quantity,
                                ];
                            })->toArray(),
                        ];
                    })->values();
                }
                $unitPrice = $invoiceProduct->unit_price ?? 0;
                $ncFinanciero = $financialNCsByIP[$invoiceProduct->id] ?? 0;
                $effectiveUnitPrice = $cantidadOriginal > 0
                    ? round($unitPrice - ($ncFinanciero / $cantidadOriginal), 2)
                    : $unitPrice;

                $rows[] = [
                    'origen'            => $invoice->typeDocument?->name ?? 'factura',
                    'document_id'       => $invoice->id,
                    'number_document'   => $invoice->number_document,
                    'supplier'          => $invoice->supplier->name ?? '-',
                    'product'           => $invoiceProduct->product->name ?? '-',
                    'unit'              => $invoiceProduct->product->unit->name ?? '-',
                    'quantity'          => $cantidadOriginal,
                    'invoice_product_id'=> $invoiceProduct->id,
                    'unit_price'        => $unitPrice,
                    'effective_unit_price' => $effectiveUnitPrice,
                    'stock'             => $stockLinea,
                    'has_credit_note'   => ($devuelto > 0),
                    'credit_note_info'  => $creditNoteInfo,
                    'mes_contable'      => $invoice->month?->name ?? '',
                    'invoice_date'      => $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('Y-m-d') : '',
                    'branch_id'         => $invoiceProduct->branch_id,
                    'branch_name'       => $invoiceProduct->branch?->name,
                ];
            }
        }

        // Traer productos de notas de débito (tipo = debito)
        $debitNotes = \App\Models\CreditDebitNote::with(['supplier', 'items.product.unit', 'items.branch:id,name'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->where('type', 'debito')
            ->get();

        foreach ($debitNotes as $note) {
            foreach ($note->items as $item) {
                if ($term && stripos($note->number, $term) === false) {
                    continue;
                }
                $consumido = $outflowsByDebitNoteItem[$item->id] ?? 0;
                $cantidadOriginal = $item->quantity ?? 0;
                $stockLinea = round($cantidadOriginal - $consumido, 2);
                // Excluir líneas con stock cero o negativo
                if ($stockLinea <= 0) {
                    continue;
                }
                $rows[] = [
                    'origen'                  => 'nota_debito',
                    'document_id'             => $note->id,
                    'number_document'         => $note->number,
                    'supplier'                => $note->supplier->name ?? '-',
                    'product'                 => $item->product->name ?? '-',
                    'unit'                    => $item->unit->name ?? '-',
                    'quantity'                => $cantidadOriginal,
                    'credit_debit_note_item_id'=> $item->id,
                    'stock'                   => $stockLinea,
                    'invoice_date'            => $note->date?->format('Y-m-d'),
                    'branch_id'               => $item->branch_id,
                    'branch_name'             => $item->branch?->name,
                ];
            }
        }

    // Paginación manual (mostrar todas las filas sin límite)
    $page = $request->input('page', 1);
    // Ajustar perPage al total de filas para no limitar los resultados
    $perPage = max(1, count($rows));
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Cargar catálogos para selects
        $projects = \App\Models\Project::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get()
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);
        $operations = \App\Models\Operation::all()
            ->map(fn($o) => ['value' => $o->id, 'label' => $o->name]);
        $investments = \App\Models\Investment::where('season_id', $season_id)
            ->get()
            ->map(fn($i) => ['value' => $i->id, 'label' => $i->name]);
        $machineries = \App\Models\Machinery::where('team_id', $user->team_id)
            ->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => trim($m->cod_machinery . ' - ' . $m->brand)
            ]);
        $cost_centers = \App\Models\CostCenter::where('season_id', $season_id)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->name, 'branch_id' => $c->branch_id]);

        // Traer detalles de salidas ya registradas con sus centros de costo
        // 🔥 OPTIMIZACIÓN: Eager loading completo para evitar N+1 queries
        $outflowDetails = Outflow::with([
            'costCenters.costCenter',
            'project',
            'operation',
            'machinery',
            'user',
            'invoiceProduct.product',
            'invoiceProduct.branch:id,name',
            'invoiceProduct.invoice.supplier',
            'invoiceProduct.invoice.month',
            'creditDebitNoteItem.product',
            'creditDebitNoteItem.branch:id,name',
            'creditDebitNoteItem.creditDebitNote.supplier',
            'creditDebitNoteItem.creditDebitNote.month',
            'fuelOutflow.invoiceProduct.branch:id,name',
            'fuelOutflow.invoiceProduct.invoice.supplier',
            'fuelOutflow.invoiceProduct.invoice.month',
            'fuelOutflow.product',
            'level3.level2.level1',
            'investment'
        ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderByDesc('id')
            ->get()
            ->map(function($outflow) {
                return [
                    'id' => $outflow->id,
                    'fuel_outflow_id' => $outflow->fuel_outflow_id,
                    'date' => $outflow->date ? \Carbon\Carbon::parse($outflow->date)->format('d-m-Y') : '',
                    'fecha_factura' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->invoice->date ? \Carbon\Carbon::parse($outflow->invoiceProduct->invoice->date)->format('d-m-Y') : '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->creditDebitNote->date ? \Carbon\Carbon::parse($outflow->creditDebitNoteItem->creditDebitNote->date)->format('d-m-Y') : '')
                            : ($outflow->fuelOutflow?->invoiceProduct?->invoice?->date
                                ? \Carbon\Carbon::parse($outflow->fuelOutflow->invoiceProduct->invoice->date)->format('d-m-Y') : '')),
                    'mes_contable' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->invoice->month?->name ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->creditDebitNote->month?->name ?? '')
                            : ($outflow->fuelOutflow?->invoiceProduct?->invoice?->month?->name ?? '')),
                    'number_document' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->invoice->number_document ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->creditDebitNote->number_document ?? '')
                            : ($outflow->fuelOutflow?->invoiceProduct?->invoice?->number_document ?? '')),
                    'supplier' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->invoice->supplier->name ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->creditDebitNote->supplier->name ?? '')
                            : ($outflow->fuelOutflow?->invoiceProduct?->invoice?->supplier?->name ?? '')),
                    'supplier_rut' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->invoice->supplier->rut ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->creditDebitNote->supplier->rut ?? '')
                            : ($outflow->fuelOutflow?->invoiceProduct?->invoice?->supplier?->rut ?? '')),
                    'project' => $outflow->project->name ?? '',
                    'operation' => $outflow->operation->name ?? '',
                    'investment' => $outflow->investment->name ?? '',
                    'machinery' => $outflow->machinery ? trim($outflow->machinery->cod_machinery . ' - ' . $outflow->machinery->brand) : '',
                    // Nombre del producto según origen
                    'product' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->product->name ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->product->name ?? '')
                            : ($outflow->fuelOutflow
                                ? ($outflow->fuelOutflow->product->name ?? '')
                                : '')),
                    'quantity' => $outflow->quantity,
                    'unit_price' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->unit_price ?? null)
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->unit_price ?? null)
                            : ($outflow->fuelOutflow
                                ? ($outflow->fuelOutflow->invoiceProduct->unit_price ?? null)
                                : null)),
                    'notes' => $outflow->notes,
                    'cost_centers' => $outflow->costCenters->map(function($cc) {
                        return [
                            'id'           => $cc->costCenter->id ?? null,
                            'name'         => $cc->costCenter->name ?? '',
                            'surface'      => $cc->costCenter->surface ?? 0,
                            'observations' => $cc->observations,
                        ];
                    })->toArray(),
                    'user' => $outflow->user->name ?? '',
                    'level1_name' => $outflow->level3->level2->level1->name ?? null,
                    'level2_name' => $outflow->level3->level2->name ?? null,
                    'level3_name' => $outflow->level3->name ?? null,
                    'branch_name' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->branch?->name ?? null)
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->branch?->name ?? null)
                            : ($outflow->fuelOutflow?->invoiceProduct?->branch?->name ?? null)),
                ];
            });

  // Agrupaciones para el select de agrupación
    $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($season_id, $user) {
        $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $season_id);
    }])
    ->where('season_id', $season_id)
    ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
    ->get()
    ->map(fn($g) => [
        'id' => $g->id,
        'name' => $g->name,
        'cost_centers' => $g->costCenters->map(fn($cc) => [
            'id' => $cc->id,
            'name' => $cc->name
        ])->values(),
    ]);

        // Obtener levels2 para el select de filtro (filtrado por team y season)
        $levels2 = \App\Models\Level2::with(['level1'])
            ->whereHas('level1', function($query) use ($user, $season_id) {
                $query->where('team_id', $user->team_id)
                      ->where('season_id', $season_id);
            })
            ->get()
            ->filter(function($level2) {
                return $level2->level1 !== null;
            })
            ->map(function($level2) {
                return [
                    'value' => $level2->id,
                    'label' => $level2->name . ' (' . $level2->level1->name . ')',
                    'level2_name' => $level2->name,
                    'level1_name' => $level2->level1->name,
                ];
            })
            ->values();

        // Obtener levels3 con jerarquía completa y level2_id para filtrado (filtrado por team y season)
        $levels3 = \App\Models\Level3::with(['level2.level1'])
            ->whereHas('level2.level1', function($query) use ($user, $season_id) {
                $query->where('team_id', $user->team_id)
                      ->where('season_id', $season_id);
            })
            ->get()
            ->filter(function($level3) {
                return $level3->level2 !== null && $level3->level2->level1 !== null;
            })
            ->map(function($level3) {
                return [
                    'value' => $level3->id,
                    'label' => $level3->name,
                    'level3_name' => $level3->name,
                    'level2_id' => $level3->level2_id,
                    'level2_name' => $level3->level2->name,
                    'level1_name' => $level3->level2->level1->name,
                ];
            })
            ->values();

        return Inertia::render('Outflows', [
            'outflows' => $paginated,
            'term'     => $term,
            'projects' => $projects,
            'operations' => $operations,
            'investments' => $investments,
            'machineries' => $machineries,
            'cost_centers' => $cost_centers,
            'outflowDetails' => $outflowDetails,
            'groupings' => $groupings,
            'levels2' => $levels2,
            'levels3' => $levels3,
            'branches' => Branch::where('team_id', $user->team_id)
                ->where('season_id', $season_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($b) => ['value' => $b->id, 'label' => $b->name]),
        ]);
    }
}
