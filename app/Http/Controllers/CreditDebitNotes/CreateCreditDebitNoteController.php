<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unit;
use Inertia\Inertia;

class CreateCreditDebitNoteController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
        });

        $invoices = Invoice::where('team_id', $user->team_id)->get()->transform(function($invoice){
            // Obtener productos de la factura
            $productosFactura = $invoice->products()->get()->map(function($product) {
                return [
                    'label'      => $product->name,
                    'value'      => $product->id,
                    'unit_id'    => $product->unit_id,
                    'unit_price' => $product->pivot->unit_price,
                    'amount'     => $product->pivot->amount,
                ];
            })->values()->all();
            return [
                'label' => $invoice->number_document,
                'value' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'products' => $productosFactura
            ];
        });

        $products = Product::where('team_id', $user->team_id)->get()->transform(function($product){
            return [
                'label'   => $product->name,
                'value'   => $product->id,
                'unit_id' => $product->unit_id,
            ];
        });

        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

        return Inertia::render('CreditDebitNotes/Create', compact('suppliers', 'invoices', 'products', 'units'));
    }
}
