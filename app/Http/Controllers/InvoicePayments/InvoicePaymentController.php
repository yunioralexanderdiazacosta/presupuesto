<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use App\Models\Bank;
use App\Models\Supplier;
use Inertia\Inertia;

class InvoicePaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $term          = $request->term ?? '';
        $dateFrom      = $request->date_from ?? '';
        $dateTo        = $request->date_to ?? '';
        $supplierId    = $request->supplier_id ?? '';
        $paymentStatus = $request->payment_status ?? '';

        // Query base: Facturas del equipo/temporada con totales calculados via subquery
        // total_neto: suma pura de productos (sin IVA)
        // total_invoice: neto × 1.19 para FACTURA/NOTA CREDITO/NOTA DEBITO, neto para el resto
        $query = Invoice::select('invoices.*')
            ->selectRaw('(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) as total_neto')
            ->selectRaw("(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) * CASE WHEN UPPER(td.name) IN ('FACTURA', 'NOTA CREDITO', 'NOTA DEBITO') THEN 1.19 ELSE 1.0 END as total_invoice")
            ->selectRaw('(SELECT COALESCE(SUM(pay.amount), 0) FROM invoice_payments pay WHERE pay.invoice_id = invoices.id) as total_paid')
            ->leftJoin('type_documents as td', 'td.id', '=', 'invoices.type_document_id')
            ->with(['supplier', 'typeDocument', 'payments.bank', 'payments.user'])
            ->where('invoices.team_id', $user->team_id)
            ->where('invoices.season_id', $season_id)
            ->when($term, function ($q, $search) {
                $q->where(function($q2) use ($search) {
                    $q2->where('invoices.number_document', 'like', '%'.$search.'%')
                       ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($dateFrom, fn($q, $date) => $q->whereDate('invoices.date', '>=', $date))
            ->when($dateTo,   fn($q, $date) => $q->whereDate('invoices.date', '<=', $date))
            ->when($supplierId, fn($q, $id) => $q->where('invoices.supplier_id', $id));

        // Filtro por estado de pago usando HAVING (sobre los subqueries)
        if ($paymentStatus === 'pending') {
            $query->havingRaw('total_paid = 0');
        } elseif ($paymentStatus === 'paid') {
            $query->havingRaw('total_paid >= total_invoice AND total_invoice > 0');
        } elseif ($paymentStatus === 'partial') {
            $query->havingRaw('total_paid > 0 AND total_paid < total_invoice');
        }

        $invoices = $query->orderByDesc('invoices.date')
            ->paginate(50)
            ->through(function ($invoice) {
                $totalNeto    = (float) $invoice->total_neto;
                $totalPaid    = (float) $invoice->total_paid;

                // Calcular IVA en PHP con el typeDocument ya cargado (más fiable que el CASE SQL)
                $tipoDoc      = strtoupper($invoice->typeDocument?->name ?? '');
                $hasIva       = in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO']);
                $iva          = $hasIva ? round($totalNeto * 0.19) : 0;
                $totalInvoice = $totalNeto + $iva;
                $balance      = $totalInvoice - $totalPaid;

                if ($totalPaid >= $totalInvoice && $totalInvoice > 0) {
                    $status = 'paid';
                } elseif ($totalPaid > 0) {
                    $status = 'partial';
                } else {
                    $status = 'pending';
                }

                return [
                    'id'              => $invoice->id,
                    'number_document' => $invoice->number_document,
                    'date'            => $invoice->date,
                    'due_date'        => $invoice->due_date,
                    'supplier'        => $invoice->supplier
                        ? ['id' => $invoice->supplier->id, 'name' => $invoice->supplier->name]
                        : null,
                    'type_document'   => $invoice->typeDocument?->name,
                    'total_neto'      => $totalNeto,
                    'iva'             => $iva,
                    'total_invoice'   => $totalInvoice,
                    'total_paid'      => $totalPaid,
                    'balance'         => $balance,
                    'payment_status'  => $status,
                    'payments'        => $invoice->payments->map(fn($p) => [
                        'id'                  => $p->id,
                        'payment_date'        => $p->payment_date,
                        'amount'              => $p->amount,
                        'payment_method'      => $p->payment_method,
                        'payment_method_name' => $p->payment_method_name,
                        'bank_id'             => $p->bank_id,
                        'bank'                => $p->bank?->name,
                        'transaction_number'  => $p->transaction_number,
                        'observations'        => $p->observations,
                        'user'                => $p->user?->name,
                        'number_document'     => $invoice->number_document,
                    ])->values(),
                ];
            });

        // Obtener bancos activos
        $banks = Bank::where('active', true)->orderBy('name')->get(['id', 'name']);

        // Obtener proveedores del equipo
        $suppliers = Supplier::where('team_id', $user->team_id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('InvoicePayments/Index', [
            'invoices'  => $invoices,
            'banks'     => $banks,
            'suppliers' => $suppliers,
            'filters'   => [
                'term'           => $term,
                'date_from'      => $dateFrom,
                'date_to'        => $dateTo,
                'supplier_id'    => $supplierId,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    // Método para buscar facturas (API endpoint)
    public function searchInvoices(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $query = Invoice::with(['supplier', 'typeDocument', 'companyReason', 'invoiceProducts'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id);

        if ($request->number_document) {
            // Buscar facturas que contengan el número buscado
            $query->where('number_document', 'like', '%'.$request->number_document.'%');
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Obtener resultados limitados a 50
        $invoices = $query->limit(50)->get()->map(function($invoice) {
            $totalNeto = $invoice->invoiceProducts->sum(fn($ip) => $ip->unit_price * $ip->amount);

            $tipoDoc = strtoupper($invoice->typeDocument?->name ?? '');
            $hasIva  = in_array($tipoDoc, ['FACTURA', 'NOTA CREDITO', 'NOTA DEBITO']);
            $iva     = $hasIva ? round($totalNeto * 0.19) : 0;
            $totalInvoice = $totalNeto + $iva;

            $totalPaid = $invoice->payments()->sum('amount');
            $balance = $totalInvoice - $totalPaid;

            return [
                'id' => $invoice->id,
                'number_document' => $invoice->number_document,
                'date' => $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') : null,
                'due_date' => $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') : null,
                'supplier' => $invoice->supplier ? ['id' => $invoice->supplier->id, 'name' => $invoice->supplier->name] : null,
                'type_document' => $invoice->typeDocument ? $invoice->typeDocument->name : null,
                'company_reason' => $invoice->companyReason ? $invoice->companyReason->name : null,
                'total_neto'    => $totalNeto,
                'iva'           => $iva,
                'total_invoice' => $totalInvoice,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'payment_status' => $totalPaid == 0 ? 'pending' : ($totalPaid >= $totalInvoice ? 'paid' : 'partial'),
            ];
        });

        return response()->json($invoices);
    }
}
