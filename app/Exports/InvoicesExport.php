<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;

class InvoicesExport implements FromView, ShouldAutoSize
{
    public $term;

    public function __construct($term)
    {
        $this->term = $term;
    }

    public function view(): View
    {
        $user = Auth::user();
        
        $season_id = session('season_id');

        $term = $request->term ?? '';

        $invoices = Invoice::with('supplier', 'companyReason')->when($this->term, function ($query, $search) {
            $query->where('number_document', 'like', '%'.$search.'%');
        })
        ->OrWhereHas('supplier', function($query) use ($term){
            $query->where('name', 'like', '%'.$term.'%');
        })
        ->OrWhereHas('companyReason', function($query) use ($term){
            $query->where('name', 'like', '%'.$term.'%');
        })
        ->where('team_id', $user->team_id)->where('season_id', $season_id)
        ->get()
        ->transform(function($invoice){
            return [
                'id'                => $invoice->id,
                'date'              => $invoice->date,
                'due_date'          => $invoice->due_date,
                'supplier'          => $invoice->supplier,
                'companyReason'     => $invoice->companyReason,
                'number_document'   => $invoice->number_document,
                'total'             => $this->get_total($invoice)
            ];
        }); 

        return view('excels.invoices', [
            'invoices' => $invoices
        ]);
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
