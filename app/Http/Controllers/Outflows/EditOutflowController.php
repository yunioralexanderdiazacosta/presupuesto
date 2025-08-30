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
        if ($outflow->invoice_product_id) {
            $ip = $outflow->invoiceProduct;
            $original = $ip->quantity ?? $ip->amount ?? 0;
            // Consumido por otras salidas (excluyendo esta)
            $consumed = \App\Models\Outflow::where('invoice_product_id', $ip->id)
                ->where('id', '<>', $outflow->id)
                ->sum('quantity');
            // Devuelto por notas de crédito
            $returned = \Illuminate\Support\Facades\DB::table('credit_debit_note_items')
                ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                ->where('credit_debit_note_items.invoice_product_id', $ip->id)
                ->where('credit_debit_notes.type', 'credito')
                ->sum('credit_debit_note_items.quantity');
            $stockAvailable = $original - $consumed - $returned;
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
                    })->toArray()
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
