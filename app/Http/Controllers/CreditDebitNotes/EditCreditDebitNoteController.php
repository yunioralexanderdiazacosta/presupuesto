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

        $suppliers = Supplier::where('team_id', $user->team_id)->get()->transform(function($supplier){
            return [
                'label' => $supplier->name,
                'value' => $supplier->id
            ];
        });

        $invoices = Invoice::where('team_id', $user->team_id)->get()->transform(function($invoice){
            return [
                'label' => $invoice->number_document,
                'value' => $invoice->id
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
                'product_id'   => $item->product_id,
                'unit_id'      => $item->unit_id,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
            ];
        });

        return Inertia::render('CreditDebitNotes/Edit', compact('note', 'suppliers', 'invoices', 'products', 'units', 'items'));
    }
}
