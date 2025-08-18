<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormCreditDebitNoteRequest;
use App\Models\CreditDebitNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UpdateCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note, FormCreditDebitNoteRequest $request)
    {
        DB::transaction(function() use ($note, $request) {
            $note->update([
                'type'              => $request->type,
                'invoice_id'        => $request->invoice_id,
                'supplier_id'       => $request->supplier_id,
                'number'            => $request->number,
                'date'              => $request->date,
                'reason'            => $request->reason,
                'affects_inventory' => $request->affects_inventory ?? false,
            ]);

            // Actualizar items: eliminar y volver a crear (simple y seguro)
            $note->items()->delete();
            foreach ($request->items as $item) {
                $note->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id'    => $item['unit_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }
        });
    }
}
