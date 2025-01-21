<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UpdateInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice, FormInvoiceRequest $request)
    {
        $invoice->number            = $request->number;
        $invoice->payment_term      = $request->payment_term;
        $invoice->payment_type      = $request->payment_type;
        $invoice->petty_cash        = $request->petty_cash;
        $invoice->supplier_id       = $request->supplier_id;
        $invoice->company_reason_id = $request->company_reason_id;
        $invoice->type_document_id  = $request->type_document_id;
        $invoice->date              = $request->date;
        $invoice->due_date          = $request->due_date;
        $invoice->save();

        $invoice->products()->sync($this->products($request->products));
    }

    public function products($products)
    {
        $data = array();
        foreach($products as $product){
            $data[$product['product_id']] = [
                'unit_price' => $product['unit_price'], 
                'amount' => $product['amount'], 
                'observations' => $product['observations']
            ];
        }
        return $data;
    }
}
