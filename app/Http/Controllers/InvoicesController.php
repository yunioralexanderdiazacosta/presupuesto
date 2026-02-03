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

    $invoices = Invoice::with([
            'supplier:id,name',
            'companyReason:id,name',
            'typeDocument:id,name',
            'month:id,name',
            'invoiceProducts.product:id,name,level1_id'
        ])
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
            // Calcular totales una sola vez usando la relación ya cargada
            $total_neto = 0;
            foreach($invoice->invoiceProducts as $ip) {
                $total_neto += ($ip->unit_price * $ip->amount);
            }
            
            // Calcular IVA solo si es factura
            $tipo_doc = $invoice->typeDocument ? strtolower($invoice->typeDocument->name) : '';
            $iva = ($tipo_doc === 'factura') ? ($total_neto * 0.19) : 0;
            $total_general = $total_neto + $iva;
            
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
                'total'             => number_format($total_neto, 2, ',', '.'),
                'iva'               => $iva > 0 ? number_format($iva, 0, ',', '.') : null,
                'total_general'     => number_format($total_general, 0, ',', '.')
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
}
