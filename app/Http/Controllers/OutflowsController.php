<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outflow;
use App\Models\Invoice;
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

        // Precalcular devoluciones por invoice_product_id en notas de crédito
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');


        // Obtener inventario actual
        $inventario = collect($this->getInventory($user->team_id, $season_id));

        // Traer productos de facturas
        $invoices = Invoice::with(['supplier', 'invoiceProducts.product.unit'])
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

        $rows = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                if ($term && stripos($invoice->number_document, $term) === false) {
                    continue;
                }
                $consumido = $outflowsByInvoiceProduct[$invoiceProduct->id] ?? 0;
                $devuelto = $creditNotesReturns[$invoiceProduct->id] ?? 0;
                $cantidadOriginal = $invoiceProduct->quantity ?? $invoiceProduct->amount ?? 0;
                $stockLinea = $cantidadOriginal - $consumido - $devuelto;
                $rows[] = [
                    'origen'            => 'factura',
                    'document_id'       => $invoice->id,
                    'number_document'   => $invoice->number_document,
                    'supplier'          => $invoice->supplier->name ?? '-',
                    'product'           => $invoiceProduct->product->name ?? '-',
                    'unit'              => $invoiceProduct->product->unit->name ?? '-',
                    'quantity'          => $cantidadOriginal,
                    'invoice_product_id'=> $invoiceProduct->id,
                    'stock'             => $stockLinea,
                ];
            }
        }

        // Traer productos de notas de débito (tipo = debito)
        $debitNotes = \App\Models\CreditDebitNote::with(['supplier', 'items.product.unit'])
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
                $stockLinea = $cantidadOriginal - $consumido;
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
                ];
            }
        }

        // Paginación manual
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Cargar catálogos para selects
        $projects = \App\Models\Project::where('team_id', $user->team_id)
            ->get()
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name]);
        $operations = \App\Models\Operation::all()
            ->map(fn($o) => ['value' => $o->id, 'label' => $o->name]);
        $machineries = \App\Models\Machinery::where('team_id', $user->team_id)
            ->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => trim($m->cod_machinery . ' - ' . $m->brand)
            ]);
        $cost_centers = \App\Models\CostCenter::whereHas('season', function($q) use ($user) {
            $q->where('team_id', $user->team_id);
        })->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);

        // Traer detalles de salidas ya registradas con sus centros de costo
        // Detalles de salidas ya registradas
        $outflowDetails = Outflow::with([
            'costCenters.costCenter',
            'project',
            'operation',
            'machinery',
            'user',
            'invoiceProduct.product',
            'creditDebitNoteItem.product'
        ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->orderByDesc('date')
            ->take(100)
            ->get()
            ->map(function($outflow) {
                return [
                    'id' => $outflow->id,
                    'date' => $outflow->date,
                    'project' => $outflow->project->name ?? '',
                    'operation' => $outflow->operation->name ?? '',
                    'machinery' => $outflow->machinery ? trim($outflow->machinery->cod_machinery . ' - ' . $outflow->machinery->brand) : '',
                    // Nombre del producto según origen
                    'product' => $outflow->invoiceProduct
                        ? ($outflow->invoiceProduct->product->name ?? '')
                        : ($outflow->creditDebitNoteItem
                            ? ($outflow->creditDebitNoteItem->product->name ?? '')
                            : ''),
                    'quantity' => $outflow->quantity,
                    'notes' => $outflow->notes,
                    'cost_centers' => $outflow->costCenters->map(function($cc) {
                        return [
                            'name' => $cc->costCenter->name ?? '',
                            'observations' => $cc->observations
                        ];
                    })->toArray(),
                    'user' => $outflow->user->name ?? '',
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

        return Inertia::render('Outflows', [
            'outflows' => $paginated,
            'term'     => $term,
            'projects' => $projects,
            'operations' => $operations,
            'machineries' => $machineries,
            'cost_centers' => $cost_centers,
            // Detalles de salidas ya mapeados incluyendo 'product'
            'outflowDetails' => $outflowDetails,
            'groupings' => $groupings,
        ]);
    }
}
