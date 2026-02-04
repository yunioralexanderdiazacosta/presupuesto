<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditDebitNote;
use App\Models\Supplier;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Season;
use Inertia\Inertia;

class EditCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note)
    {
        $user = Auth::user();

        // Cargar relaciones necesarias
        $note->load(['supplier', 'invoice']);

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
        });

        $invoices = Invoice::where('team_id', $user->team_id)->get()->transform(function($invoice){
            return [
                'label' => $invoice->number_document,
                'value' => $invoice->id,
                'supplier_id' => $invoice->supplier_id,
                'products' => $invoice->invoiceProducts->map(function($ip) {
                    return [
                        'value' => $ip->id,
                        'product_id' => $ip->product_id,
                        'unit_id' => $ip->product->unit_id ?? null,
                        'amount' => $ip->amount,
                        'unit_price' => $ip->unit_price,
                    ];
                })->toArray()
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

        $items = $note->items()->with(['product', 'unit'])->get()->transform(function($item){
            return [
                'invoice_product_id' => $item->invoice_product_id,
                'product_id'   => $item->product_id,
                'unit_id'      => $item->unit_id,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
            ];
        });

        // Si es petición AJAX, retornar JSON
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(compact('note', 'suppliers', 'invoices', 'products', 'units', 'items'));
        }

        // Agregar supplier_id e invoice_id al objeto note para que el form los encuentre
        $noteData = $note->toArray();
        $noteData['supplier_id'] = $note->supplier_id;
        $noteData['invoice_id'] = $note->invoice_id;

        return Inertia::render('CreditDebitNotes/Edit', [
            'note' => $noteData,
            'suppliers' => $suppliers,
            'invoices' => $invoices,
            'products' => $products,
            'units' => $units,
            'items' => $items
        ]);
    }
}
