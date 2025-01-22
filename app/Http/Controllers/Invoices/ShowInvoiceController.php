<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Inertia\Inertia;

class ShowInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoiceProducts = $invoice->products()->get()->transform(function($product){
            return [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'unit_price'    => $product->pivot->unit_price,
                'amount'        => $product->pivot->amount,
                'observations'  => $product->pivot->observations
            ];  
        });

        $supplier = $invoice->supplier;
        $companyReason = $invoice->companyReason;
        $typeDocument = $invoice->typeDocument;

        $total = 0;
        $products = $invoice->products()->get();
        foreach($products as $product)
        {
            $total = $total + ($product->pivot->unit_price * $product->pivot->amount);    
        }

        $grant_total = number_format($total, 2, ',', '.');

        return Inertia::render('Invoices/Show', compact('invoice', 'supplier', 'companyReason', 'invoiceProducts', 'typeDocument', 'grant_total'));
    }


}
