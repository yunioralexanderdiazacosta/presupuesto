<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormInvoiceRequest;
use App\Models\Invoice;

class UpdateInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice, FormInvoiceRequest $request)
    {
        $invoice->payment_term      = $request->payment_term;
        $invoice->payment_type      = $request->payment_type;
        $invoice->petty_cash        = $request->petty_cash;
        $invoice->supplier_id       = $request->supplier_id;
        $invoice->company_reason_id = $request->company_reason_id;
        $invoice->type_document_id  = $request->type_document_id;
        $invoice->number_document   = $request->number_document;
        $invoice->date              = $request->date;
        $invoice->due_date          = $request->due_date;
        $invoice->save();

        $invoice->products()->sync($this->products($request->products));
    }

    public function products($products)
    {
        $data = [];
        foreach ($products as $item) {
            // Gestionar unidad: buscar o crear
            $unitId = $item['unit_id'] ?? null;
            if (!is_numeric($unitId) || !\App\Models\Unit::find($unitId)) {
                $u = \App\Models\Unit::firstOrCreate(['name' => $unitId]);
                $unitId = $u->id;
            }
            // Gestionar producto: buscar o crear
            $prodId = $item['product_id'];
            if (!is_numeric($prodId) || !\App\Models\Product::find($prodId)) {
                $newProduct = \App\Models\Product::create([
                    'name'    => $prodId,
                    'team_id' => auth()->user()->team_id,
                    'unit_id' => $unitId,
                ]);
                $prodId = $newProduct->id;
            }
            $data[$prodId] = [
                'unit_price'   => $item['unit_price'],
                'amount'       => $item['amount'],
                'observations' => $item['observations'],
            ];
        }
        return $data;
    }
}
