<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $invoices = Invoice::with('supplier', 'companyReason')->where('team_id', $user->team_id)->paginate(10)->through(function($invoice){
            return [
                'id'                => $invoice->id,
                'date'              => $invoice->date,
                'due_date'          => $invoice->due_date,
                'number'            => $invoice->number,
                'supplier'          => $invoice->supplier,
                'companyReason'     => $invoice->companyReason,
                'number_document'   => $invoice->number_document,
                'total'         => $this->get_total($invoice)
            ];
        }); 

        return Inertia::render('Invoices', compact('invoices'));
    }

    private function get_total($invoice)
    {
        $total = 0;
        $products = $invoice->products()->get();

        foreach($products as $product)
        {
            $total = $total + ($product->pivot->unit_price * $product->pivot->amount);    
        }

        return number_format($total, 2, ',', '.');
    }
}
