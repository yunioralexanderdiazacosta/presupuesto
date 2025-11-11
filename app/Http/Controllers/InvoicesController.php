<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\Level1;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $term = $request->term ?? '';

    $invoices = Invoice::with('supplier', 'companyReason', 'products.level1', 'typeDocument', 'month')
        ->where('team_id', $user->team_id)
        ->where('season_id', $season_id)
        ->when($request->term, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('number_document', 'like', '%'.$search.'%')
                  ->orWhereHas('supplier', function($query) use ($search){
                      $query->where('name', 'like', '%'.$search.'%');
                  })
                  ->orWhereHas('companyReason', function($query) use ($search){
                      $query->where('name', 'like', '%'.$search.'%');
                  });
            });
        })
        ->paginate(1500)
        ->withQueryString()
        ->through(function($invoice){
            return [
                'id'                => $invoice->id,
                'date'              => $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') : null,
                'due_date'          => $invoice->due_date,
                'supplier'          => $invoice->supplier,
                'companyReason'     => $invoice->companyReason,
                'type_document'     => $invoice->typeDocument ? $invoice->typeDocument->name : null,
                'month'             => $invoice->month ? $invoice->month->name : null,
                'number_document'   => $invoice->number_document,
                'products'          => $invoice->invoiceProducts->map(function($ip){
                                            return [
                                                'id'           => $ip->id,
                                                'product_id'   => $ip->product_id,
                                                'product_name' => $ip->product ? $ip->product->name : null,
                                                'level1_id'    => $ip->product ? $ip->product->level1_id : null,
                                                'unit_price'   => $ip->unit_price,
                                                'amount'       => $ip->amount,
                                                'observations' => $ip->observations,
                                            ];
                                        }),
                'total'             => $this->get_total($invoice)
            ];
        }); 

            // Pasar también la lista de meses al frontend para la tabla pivot
            $months = \App\Models\Month::orderBy('id')
                ->get()
                ->transform(function($month) {
                    return [
                        'label' => $month->name,
                        'value' => $month->id,
                    ];
                });
            
            // Obtener units y level1s para el modal de edición de productos
            $units = Unit::orderBy('name')
                ->get()
                ->transform(function($unit) {
                    return [
                        'label' => $unit->name,
                        'value' => $unit->id,
                    ];
                });
            
            $level1s = Level1::where('season_id', $season_id)
                ->orderBy('name')
                ->get()
                ->transform(function($level) {
                    return [
                        'label' => $level->name,
                        'value' => $level->id,
                    ];
                });
            
            return Inertia::render('Invoices', compact('invoices', 'term', 'months', 'units', 'level1s'));
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
