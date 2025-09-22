<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\Project;
use App\Models\Operation;
use App\Models\Machinery;
use App\Models\Team;
use App\Models\Season;
use App\Models\CostCenter;
use Inertia\Inertia;


class EditOutflowController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request, Outflow $outflow)
    {
        // Cargar relaciones necesarias
        $outflow->load(['costCenters.costCenter', 'invoiceProduct.product.unit', 'creditDebitNoteItem.product.unit', 'project', 'operation', 'machinery']);
        // Calcular stock disponible para la línea de factura
        $stockAvailable = 0;
        $hasCreditNote = false;
        $creditNoteInfo = null;
        if ($outflow->invoice_product_id) {
            $ip = $outflow->invoiceProduct;
            $original = $ip->quantity ?? $ip->amount ?? 0;
            $consumed = \App\Models\Outflow::where('invoice_product_id', $ip->id)
                ->where('id', '<>', $outflow->id)
                ->sum('quantity');
            // Buscar la(s) nota(s) de crédito asociada(s) a este invoice_product
            $creditNotes = \App\Models\CreditDebitNote::where('type', 'credito')
                ->whereHas('items', function($q) use ($ip) {
                    $q->where('invoice_product_id', $ip->id);
                })
                ->with(['supplier', 'items' => function($q) use ($ip) {
                    $q->where('invoice_product_id', $ip->id);
                }, 'items.product'])
                ->get();
            $returned = 0;
            if ($creditNotes->count() > 0) {
                $hasCreditNote = true;
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
                // Sumar la cantidad devuelta
                $returned = $creditNotes->flatMap->items->sum('quantity');
            }
            $stockAvailable = $original - $consumed - $returned;
        } else if ($outflow->credit_debit_note_item_id) {
            $item = $outflow->creditDebitNoteItem;
            $original = $item->quantity ?? 0;
            $consumed = \App\Models\Outflow::where('credit_debit_note_item_id', $item->id)
                ->where('id', '<>', $outflow->id)
                ->sum('quantity');
            $stockAvailable = $original - $consumed;
        }
        $data = [
            'outflow' => array_merge(
                $outflow->toArray(),
                [
                    'cost_centers' => $outflow->costCenters->map(function($cc) {
                        return [
                            'id' => $cc->cost_center_id,
                            'name' => $cc->costCenter->name ?? '',
                        ];
                    })->toArray(),
                    'has_credit_note' => $hasCreditNote,
                    'credit_note_info' => $creditNoteInfo,
                ]
            ),
            'projects' => Project::all(),
            'operations' => Operation::all(),
            'machineries' => Machinery::all(),
            'costCenters' => CostCenter::all(),
            'stockAvailable' => $stockAvailable,
        ];
        // Si es petición AJAX (modal), devolver JSON
        if ($request->wantsJson()) {
            return response()->json($data);
        }
        // Inertia render para la página completa
        return Inertia::render('Outflows/Edit', $data);
    }
}
