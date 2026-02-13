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

        $term = $request->term ?? '';
        $dateFrom = $request->date_from ?? '';
        $dateTo = $request->date_to ?? '';
        $supplierId = $request->supplier_id ?? '';
        $paymentMethod = $request->payment_method ?? '';
        $bankId = $request->bank_id ?? '';

        // Obtener pagos con búsqueda y filtros
        $payments = InvoicePayment::with(['invoice.supplier', 'invoice.typeDocument', 'bank', 'user'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($term, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('transaction_number', 'like', '%'.$search.'%')
                      ->orWhereHas('invoice', function($query) use ($search){
                          $query->where('number_document', 'like', '%'.$search.'%');
                      })
                      ->orWhereHas('invoice.supplier', function($query) use ($search){
                          $query->where('name', 'like', '%'.$search.'%');
                      });
                });
            })
            ->when($dateFrom, function($query, $date) {
                $query->whereDate('payment_date', '>=', $date);
            })
            ->when($dateTo, function($query, $date) {
                $query->whereDate('payment_date', '<=', $date);
            })
            ->when($supplierId, function($query, $id) {
                $query->whereHas('invoice', function($q) use ($id) {
                    $q->where('supplier_id', $id);
                });
            })
            ->when($paymentMethod, function($query, $method) {
                $query->where('payment_method', $method);
            })
            ->when($bankId, function($query, $id) {
                $query->where('bank_id', $id);
            })
            ->latest('payment_date')
            ->paginate(50);

        // Obtener bancos activos
        $banks = Bank::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Obtener proveedores del equipo
        $suppliers = Supplier::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('InvoicePayments/Index', [
            'payments' => $payments,
            'banks' => $banks,
            'suppliers' => $suppliers,
            'filters' => [
                'term' => $term,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'supplier_id' => $supplierId,
                'payment_method' => $paymentMethod,
                'bank_id' => $bankId,
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
            $totalInvoice = $invoice->invoiceProducts->sum(function($ip) {
                return $ip->unit_price * $ip->amount;
            });
            
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
                'total_invoice' => $totalInvoice,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'payment_status' => $totalPaid == 0 ? 'pending' : ($totalPaid >= $totalInvoice ? 'paid' : 'partial'),
            ];
        });

        return response()->json($invoices);
    }
}
