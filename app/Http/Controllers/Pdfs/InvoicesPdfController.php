<?php

namespace App\Http\Controllers\Pdfs;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicesPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user     = Auth::user();
        $season_id = session('season_id');
        $term     = $request->term ?? '';

        $invoicesQuery = Invoice::with([
            'supplier:id,name',
            'companyReason:id,name',
            'typeDocument:id,name',
            'month:id,name',
            'invoiceProducts',
        ])
        ->where('team_id', $user->team_id)
        ->where('season_id', $season_id)
        ->orderBy('id', 'desc');

        if ($term) {
            $invoicesQuery->where(function ($q) use ($term) {
                $q->where('number_document', 'like', '%'.$term.'%')
                  ->orWhereHas('supplier', fn($q2) => $q2->where('name', 'like', '%'.$term.'%'))
                  ->orWhereHas('companyReason', fn($q2) => $q2->where('name', 'like', '%'.$term.'%'));
            });
        }

        $invoices = $invoicesQuery->get()->map(function ($invoice) {
            $totals = $invoice->calculateTotals();
            $total  = $totals['neto'] + $totals['iva'];

            return [
                'id'              => $invoice->id,
                'date'            => $invoice->date,
                'due_date'        => $invoice->due_date,
                'supplier'        => $invoice->supplier?->name ?? '—',
                'company_reason'  => $invoice->companyReason?->name ?? '—',
                'type_document'   => $invoice->typeDocument?->name ?? '—',
                'month'           => $invoice->month?->name ?? '—',
                'number_document' => $invoice->number_document,
                'neto_afecto'     => $totals['neto_afecto'],
                'exento'          => $totals['exento'],
                'neto'            => $totals['neto'],
                'iva'             => $totals['iva'],
                'total'           => $total,
            ];
        });

        $totales = [
            'neto_afecto' => $invoices->sum('neto_afecto'),
            'exento'      => $invoices->sum('exento'),
            'neto'        => $invoices->sum('neto'),
            'iva'         => $invoices->sum('iva'),
            'total'       => $invoices->sum('total'),
        ];

        $pdf = Pdf::loadView('pdfs.invoices', compact('invoices', 'totales', 'term'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('facturas.pdf');
    }
}
