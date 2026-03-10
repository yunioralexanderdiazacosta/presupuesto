<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Inertia\Inertia;

class ShowInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoice->load(['expenseReport:id,number', 'purchaseOrder:id,order_number']);

        $invoiceProducts = $invoice->invoiceProducts()->with('product')->get()->transform(function($ip){
            return [
                'product_id'    => $ip->product ? $ip->product->id : null,
                'product_name'  => $ip->product ? $ip->product->name : 'Producto eliminado',
                'unit_price'    => $ip->unit_price,
                'original_unit_price' => $ip->original_unit_price,
                'amount'        => $ip->amount,
                'observations'  => $ip->observations
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
