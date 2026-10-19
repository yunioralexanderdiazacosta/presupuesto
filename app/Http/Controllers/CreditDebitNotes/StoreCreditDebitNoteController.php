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
use App\Services\InventoryAvailabilityService;
use Illuminate\Http\Request;


use App\Traits\CheckSeasonLocked;

class StoreCreditDebitNoteController extends Controller
{
    public function __invoke(FormCreditDebitNoteRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $isCredit = strtolower($request->type) === CreditDebitNote::TYPE_CREDIT;
        $isAnnulment = $isCredit && (bool) ($request->is_annulment ?? false);
        // La anulación total siempre revierte stock (igual que en el formulario).
        $affectsInventory = $isAnnulment || (bool) ($request->affects_inventory ?? false);

        DB::transaction(function() use ($request, $user, $season_id, $isCredit, $isAnnulment, $affectsInventory) {
            // NC con devolución física: no se puede devolver lo que ya se consumió.
            if ($isCredit && $affectsInventory) {
                $returns = [];
                foreach ($request->items as $item) {
                    $ipId = (int) $item['invoice_product_id'];
                    $returns[$ipId] = ($returns[$ipId] ?? 0) + (float) $item['quantity'];
                }
                InventoryAvailabilityService::assertCanReturn($returns, $isAnnulment);
            }

            $note = CreditDebitNote::create([
                'team_id'           => $user->team_id,
                'season_id'         => $season_id,
                'type'              => $request->type,
                'invoice_id'        => $request->invoice_id,
                'supplier_id'       => $request->supplier_id,
                'number'            => $request->number,
                'date'              => $request->date,
                'reason'            => $request->reason,
                'affects_inventory' => $affectsInventory,
                'user_id'           => $user->id,
                'is_annulment'      => $isAnnulment,
            ]);

            $isFinancialCredit = $isCredit && !$affectsInventory;

            // ND: producto, unidad y sucursal salen de la línea de la factura y no se pueden alterar desde el formulario
            $invoiceLines = $isCredit
                ? collect()
                : InvoiceProduct::where('invoice_id', $request->invoice_id)->get(['id', 'product_id', 'branch_id'])->keyBy('id');

            foreach ($request->items as $item) {
                $debitLine = $isCredit ? null : $invoiceLines->get((int) ($item['invoice_product_id'] ?? 0));

                $note->items()->create([
                    'product_id'         => $debitLine?->product_id ?? $item['product_id'],
                    'unit_id'            => $item['unit_id'],
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'invoice_product_id' => $item['invoice_product_id'] ?? null,
                    // NC (crédito): la sucursal se resuelve vía invoice_product_id → invoice_products.branch_id,
                    // nunca se guarda en el item (ver CONTEXT.md). ND (débito): se copia la de la línea de factura.
                    'branch_id'          => $isCredit ? null : ($debitLine?->branch_id ?: null),
                ]);

                // NC financiera: ajustar unit_price del invoice_product
                if ($isFinancialCredit && !empty($item['invoice_product_id'])) {
                    $ip = InvoiceProduct::whereKey($item['invoice_product_id'])->lockForUpdate()->first();
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
