<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormInvoiceRequest;
use App\Models\Invoice;

class StoreInvoiceController extends Controller
{
    public function __invoke(FormInvoiceRequest $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $invoice = Invoice::create([
            'number'            => $request->number,
            'payment_term'      => $request->payment_term,
            'payment_type'      => $request->payment_type,
            'petty_cash'        => $request->petty_cash,
            'team_id'           => $user->team_id,
            'supplier_id'       => $request->supplier_id,
            'company_reason_id' => $request->company_reason_id,
            'type_document_id'  => $request->type_document_id,
            'number_document'   => $request->number_document,
            'date'              => $request->date,
            'due_date'          => $request->due_date,
            'season_id'         => $season_id
        ]);

        $invoice->products()->sync($this->products($request->products));
    }

    public function products($products)
    {
        $data = array();
        foreach($products as $product){
            $data[$product['product_id']] = [
                'unit_price' => $product['unit_price'], 
                'amount' => $product['amount'], 
                'observations' => $product['observations']
            ];
        }
        return $data;
    }
}
