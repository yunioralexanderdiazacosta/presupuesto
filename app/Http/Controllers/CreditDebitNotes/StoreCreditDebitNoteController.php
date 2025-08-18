<?php


namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\FormCreditDebitNoteRequest;
use App\Models\CreditDebitNote;
use App\Models\CreditDebitNoteItem;
use Illuminate\Http\Request;


class StoreCreditDebitNoteController extends Controller
{
    public function __invoke(FormCreditDebitNoteRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        DB::transaction(function() use ($request, $user, $season_id) {
            $note = CreditDebitNote::create([
                'team_id'           => $user->team_id,
                'season_id'         => $season_id,
                'type'              => $request->type,
                'invoice_id'        => $request->invoice_id,
                'supplier_id'       => $request->supplier_id,
                'number'            => $request->number,
                'date'              => $request->date,
                'reason'            => $request->reason,
                'affects_inventory' => $request->affects_inventory ?? false,
                'user_id'           => $user->id,
            ]);

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
