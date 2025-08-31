<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $term = $request->term ?? '';

    $invoices = Invoice::with('supplier', 'companyReason', 'products')->when($request->term, function ($query, $search) {
            $query->where('number_document', 'like', '%'.$search.'%');
        })
        ->OrWhereHas('supplier', function($query) use ($term){
            $query->where('name', 'like', '%'.$term.'%');
        })
        ->OrWhereHas('companyReason', function($query) use ($term){
            $query->where('name', 'like', '%'.$term.'%');
        })
        ->where('team_id', $user->team_id)->where('season_id', $season_id)
        ->paginate(10)
        ->withQueryString()
        ->through(function($invoice){
            return [
                'id'                => $invoice->id,
                'date'              => $invoice->date,
                'due_date'          => $invoice->due_date,
                'supplier'          => $invoice->supplier,
                'companyReason'     => $invoice->companyReason,
                'number_document'   => $invoice->number_document,
                'products'          => $invoice->products->map(function($p){
                                            return [
                                                'id' => $p->id,
                                                'product_name' => $p->name
                                            ];
                                        }),
                'total'             => $this->get_total($invoice)
            ];
        }); 

        return Inertia::render('Invoices', compact('invoices', 'term'));
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
