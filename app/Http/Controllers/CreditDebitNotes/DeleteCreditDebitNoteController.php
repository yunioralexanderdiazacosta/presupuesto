<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNote;
use App\Models\InvoiceProduct;
use Illuminate\Support\Facades\DB;

class DeleteCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note)
    {
        DB::transaction(function () use ($note) {
            $isFinancialCredit = !$note->affects_inventory
                && in_array(strtolower($note->type), ['credito', 'nc']);

            if ($isFinancialCredit) {
                // Restaurar el unit_price de cada invoice_product afectado
                foreach ($note->items as $item) {
                    if ($item->invoice_product_id) {
                        $ip = InvoiceProduct::find($item->invoice_product_id);
                        if ($ip && $ip->amount > 0) {
                            $adjustmentPerUnit = round(($item->unit_price * $item->quantity) / $ip->amount, 2);
                            $ip->unit_price = round($ip->unit_price + $adjustmentPerUnit, 2);

                            // Si vuelve al precio original, limpiar el campo
                            if ($ip->original_unit_price !== null
                                && abs($ip->unit_price - $ip->original_unit_price) < 0.01) {
                                $ip->unit_price = $ip->original_unit_price;
                                $ip->original_unit_price = null;
                            }
                            $ip->save();
                        }
                    }
                }
            }

            $note->delete();
        });
    }
}
