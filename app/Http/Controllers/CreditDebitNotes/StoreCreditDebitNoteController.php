<?php


namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\FormCreditDebitNoteRequest;
use App\Models\CreditDebitNote;
use App\Models\CreditDebitNoteItem;
use App\Models\InvoiceProduct;
use Illuminate\Http\Request;


use App\Traits\CheckSeasonLocked;

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
                'is_annulment'      => $request->is_annulment ?? false,
            ]);

            $isFinancialCredit = !($request->affects_inventory ?? false)
                && in_array(strtolower($request->type), ['credito', 'nc']);

            foreach ($request->items as $item) {
                $note->items()->create([
                    'product_id'         => $item['product_id'],
                    'unit_id'            => $item['unit_id'],
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'invoice_product_id' => $item['invoice_product_id'] ?? null,
                    // NC (crédito): la sucursal se resuelve vía invoice_product_id → invoice_products.branch_id,
                    // nunca se guarda en el item (ver CONTEXT.md). ND (débito): se guarda la elegida en el form.
                    'branch_id'          => strtolower($request->type) === 'debito' ? ($item['branch_id'] ?? null) : null,
                ]);

                // NC financiera: ajustar unit_price del invoice_product
                if ($isFinancialCredit && !empty($item['invoice_product_id'])) {
                    $ip = InvoiceProduct::find($item['invoice_product_id']);
                    if ($ip && $ip->amount > 0) {
                        // Guardar precio original solo la primera vez
                        if (is_null($ip->original_unit_price)) {
                            $ip->original_unit_price = $ip->unit_price;
                        }
                        // Descuento por unidad = (monto NC del item) / cantidad facturada
                        $adjustmentPerUnit = round(($item['unit_price'] * $item['quantity']) / $ip->amount, 2);
                        $ip->unit_price = round($ip->unit_price - $adjustmentPerUnit, 2);
                        $ip->save();
                    }
                }
            }
        });

        return back()->with('success', 'Nota de crédito/débito guardada correctamente');
    }
}
