<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\TypeDocument;
use App\Models\Supplier;
use App\Models\CompanyReason;
use App\Models\Product;
use Inertia\Inertia;

class EditInvoiceController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $user = Auth::user();

        $typeDocuments = TypeDocument::get()->transform(function($type){
            return [
                'label' => $type->name,
                'value' => $type->id
            ];
        });

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
         });

        $companyReasons = CompanyReason::where('team_id', $user->team_id)->get()->transform(function($companyReason){
            return [
                'label' => $companyReason->name,
                'value' => $companyReason->id
            ];
         });

         $products = Product::where('team_id', $user->team_id)->get()->transform(function($product){
            return [
                'label' => $product->name,
                'value' => $product->id
            ];
         });

        $invoiceProducts = $invoice->products()->get()->transform(function($product){
            return [
                'product_id'    => $product->id,
                'unit_price'    => $product->pivot->unit_price,
                'amount'        => $product->pivot->amount,
                'observations'  => $product->pivot->observations
            ];  
        });

        return Inertia::render('Invoices/Edit', compact('invoice','invoiceProducts', 'products', 'typeDocuments', 'suppliers', 'companyReasons'));
    }
}
