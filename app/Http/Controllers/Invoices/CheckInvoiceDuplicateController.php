<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Aviso previo de factura duplicada: usa el mismo criterio que la validación al guardar
 * (FormInvoiceRequest): mismo proveedor y mismo número de documento dentro de la empresa.
 */
class CheckInvoiceDuplicateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id'     => ['required', 'integer'],
            'number_document' => ['required', 'string', 'max:100'],
            'exclude_id'      => ['nullable', 'integer'],
        ]);

        $invoice = Invoice::with(['typeDocument:id,name', 'user:id,name'])
            ->where('team_id', Auth::user()->team_id)
            ->where('supplier_id', $data['supplier_id'])
            ->where('number_document', trim($data['number_document']))
            ->when($data['exclude_id'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))
            ->first();

        if (!$invoice) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists'  => true,
            'invoice' => [
                'id'              => $invoice->id,
                'number_document' => $invoice->number_document,
                'type_document'   => $invoice->typeDocument?->name,
                'date'            => $invoice->date,
                'season'          => Season::whereKey($invoice->season_id)->value('name'),
                'registered_by'   => $invoice->user?->name,
                'url'             => route('invoices.show', $invoice->id),
            ],
        ]);
    }
}
