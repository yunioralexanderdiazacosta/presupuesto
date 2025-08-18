<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNote;
use Inertia\Inertia;

class ShowCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note)
    {
        $items = $note->items()->with(['product', 'unit'])->get()->transform(function($item){
            return [
                'product_id'   => $item->product_id,
                'product_name' => $item->product->name ?? '',
                'unit_id'      => $item->unit_id,
                'unit_name'    => $item->unit->name ?? '',
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
            ];
        });

        $supplier = $note->supplier;
        $invoice  = $note->invoice;

        return Inertia::render('CreditDebitNotes/Show', compact('note', 'supplier', 'invoice', 'items'));
    }
}
