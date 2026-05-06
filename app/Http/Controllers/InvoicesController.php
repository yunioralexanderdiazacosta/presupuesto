<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Branch;
use Inertia\Inertia;

class InvoicesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $term = $request->term ?? '';

        // Obtener todas las facturas con eager loading optimizado
        $invoicesQuery = Invoice::with([
            'supplier:id,name', 
            'companyReason:id,name', 
            'typeDocument:id,name',
            'month:id,name',
            'user:id,name',
            'invoiceProducts.product:id,name,level1_id',
            'invoiceProducts.branch:id,name',
            'expenseReport:id,number',
            'creditDebitNotes:id,invoice_id,type,number,date,supplier_id',
            'creditDebitNotes.supplier:id,name',
        ])
        ->where('team_id', $user->team_id)
        ->where('season_id', $season_id)
        ->orderBy('id', 'desc');

        // Aplicar filtros de búsqueda
        if ($term) {
            $invoicesQuery->where(function($query) use ($term) {
                $query->where('number_document', 'like', '%'.$term.'%')
                    ->orWhereHas('supplier', function($q) use ($term) {
                        $q->where('name', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('companyReason', function($q) use ($term) {
                        $q->where('name', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('invoiceProducts.product', function($q) use ($term) {
                        $q->where('name', 'like', '%'.$term.'%');
                    });
            });
        }

        // Obtener todas las facturas (sin paginación para cálculos correctos)
        $allInvoices = $invoicesQuery->get();

        // Calcular totales globales
        $totalFacturas = 0;
        $totalIva = 0;
        $totalGeneral = 0;

        foreach ($allInvoices as $invoice) {
            $total = 0;
            foreach ($invoice->invoiceProducts as $ip) {
                $total += $ip->unit_price * $ip->amount;
            }
            
            $totalFacturas += $total;

            // Agregar IVA si es factura, nota de crédito o nota de débito
            $tipoDoc = strtoupper($invoice->typeDocument?->name ?? '');
            if (in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO'])) {
                $totalIva += $total * 0.19;
            }
        }

        $totalGeneral = $totalFacturas + $totalIva;

        // Preparar datos para la vista
        $invoices = $allInvoices->map(function($invoice) {
            $neto = 0;
            foreach ($invoice->invoiceProducts as $ip) {
                $neto += $ip->unit_price * $ip->amount;
            }
            $tipoDoc = strtoupper($invoice->typeDocument?->name ?? '');
            $hasIva = in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO']);
            $iva = $hasIva ? round($neto * 0.19) : 0;
            $total = $neto + $iva;

            return [
                'id'                => $invoice->id,
                'date'              => $invoice->date,
                'due_date'          => $invoice->due_date,
                'supplier'          => $invoice->supplier,
                'companyReason'     => $invoice->companyReason,
                'type_document'     => $invoice->typeDocument ? $invoice->typeDocument->name : null,
                'month'             => $invoice->month ? $invoice->month->name : null,
                'number_document'   => $invoice->number_document,
                'products'          => $invoice->invoiceProducts->map(function($ip) {
                    return [
                        'id' => $ip->product ? $ip->product->id : null,
                        'product_name' => $ip->product ? $ip->product->name : 'Producto eliminado',
                        'has_level1' => $ip->product && $ip->product->level1_id ? true : false,
                        'amount' => $ip->amount,
                        'unit_price' => $ip->unit_price,
                        'original_unit_price' => $ip->original_unit_price,
                        'branch_id' => $ip->branch_id,
                        'branch_name' => $ip->branch ? $ip->branch->name : null,
                    ];
                }),
                'neto'              => $neto,
                'iva'               => $iva,
                'total'             => '$' . number_format($total, 0, ',', '.'),
                'expense_report'    => $invoice->expenseReport ? $invoice->expenseReport->number : null,
                'user_name'         => $invoice->user ? $invoice->user->name : null,
                'has_credit_notes'  => $invoice->creditDebitNotes->where('type', 'credito')->count() > 0,
                'has_debit_notes'   => $invoice->creditDebitNotes->where('type', 'debito')->count() > 0,
                'notes_info'        => $invoice->creditDebitNotes->map(fn($n) => [
                    'type'     => $n->type,
                    'number'   => $n->number,
                    'date'     => $n->date?->format('Y-m-d'),
                    'supplier' => $n->supplier?->name ?? '',
                ])->values(),
            ];
        });

        return Inertia::render('Invoices', [
            'invoices' => [
                'data' => $invoices,
                'links' => []
            ],
            'term' => $term,
            'totalFacturas' => $totalFacturas,
            'totalIva' => $totalIva,
            'totalGeneral' => $totalGeneral,
            'branches' => Branch::where('team_id', $user->team_id)
                ->where('season_id', $season_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($b) => ['value' => $b->id, 'label' => $b->name]),
        ]);
    }
}
