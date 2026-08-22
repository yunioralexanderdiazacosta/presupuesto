<?php

namespace App\Http\Controllers\InvoicePayments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $paymentType   = $request->has('payment_type') ? $request->payment_type : '1'; // Default: Crédito

        // Query base: Facturas del equipo/temporada con totales calculados via subquery
        // total_neto: suma pura de productos (sin IVA)
        // total_invoice: neto × 1.19 (redondeado igual que Invoice::calculateDebt(), IVA redondeado antes de sumar)
        // para FACTURA/NOTA CREDITO/NOTA DEBITO, neto redondeado para el resto.
        // inv_credit_adj/debit_adj: cada nota se redondea individualmente antes de sumar (igual que calculateDebt()).
        // Redondear aquí igual que en PHP evita que una factura aparezca "Parcial" en el filtro pero "Pagada"
        // en la tabla (o viceversa) por diferencias de centavos entre el cálculo SQL sin redondear y el de PHP.
        $query = Invoice::select('invoices.*')
            ->selectRaw('(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) as total_neto')
            ->selectRaw("
                CASE WHEN UPPER(td.name) IN ('FACTURA', 'NOTA CREDITO', 'NOTA DEBITO')
                    THEN ROUND(
                        (SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id)
                        + ROUND((SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) * 0.19)
                    )
                    ELSE ROUND((SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id))
                END as total_invoice
            ")
            ->selectRaw('(SELECT COALESCE(SUM(pay.amount), 0) FROM invoice_payments pay WHERE pay.invoice_id = invoices.id) as total_paid')
            ->selectRaw('(SELECT COUNT(*) FROM credit_debit_notes cdn WHERE cdn.invoice_id = invoices.id AND cdn.is_annulment = 1) as annulment_count')
            ->selectRaw("
                ROUND(COALESCE((
                    SELECT SUM(cdni.quantity * cdni.unit_price)
                    FROM credit_debit_notes cdn
                    JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id
                    WHERE cdn.invoice_id = invoices.id AND cdn.type = 'credito' AND cdn.affects_inventory = 1 AND cdn.is_annulment = 0
                ), 0) * 1.19) as inv_credit_adj
            ")
            ->selectRaw("
                ROUND(COALESCE((
                    SELECT SUM(cdni.quantity * cdni.unit_price)
                    FROM credit_debit_notes cdn
                    JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id
                    WHERE cdn.invoice_id = invoices.id AND cdn.type = 'debito' AND cdn.is_annulment = 0
                ), 0) * 1.19) as debit_adj
            ")
            ->leftJoin('type_documents as td', 'td.id', '=', 'invoices.type_document_id')
            ->with([
                'supplier',
                'supplier.bankAccounts.bank:id,name',
                'supplier.bankAccounts.accountType:id,name',
                'typeDocument',
                'companyReason',
                'payments.bank',
                'payments.user',
                'payments.supplierBankAccount.bank:id,name',
                'payments.supplierBankAccount.accountType:id,name',
                'creditDebitNotes.items',
                'expenseReport:id,number',
            ])
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
            ->when($supplierId, fn($q, $id) => $q->where('invoices.supplier_id', $id))
            ->when($paymentType !== '', fn($q) => $q->where('invoices.payment_type', $paymentType));

        // Resumen por estado (sin filtro de payment_status para mostrar siempre los totales completos).
        // Se calcula con el mismo helper calculateDebt() que usa la tabla, para evitar diferencias de
        // centavos por redondeo entre el resumen (antes SQL crudo sin redondear) y el detalle por factura.
        $summaryInvoices = Invoice::with(['invoiceProducts', 'payments', 'typeDocument', 'creditDebitNotes.items'])
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
            ->when($supplierId, fn($q, $id) => $q->where('invoices.supplier_id', $id))
            ->when($paymentType !== '', fn($q) => $q->where('invoices.payment_type', $paymentType))
            ->get();

        $summary = [
            'total'    => ['count' => 0, 'amount'  => 0],
            'pending'  => ['count' => 0, 'amount'  => 0],
            'partial'  => ['count' => 0, 'balance' => 0],
            'paid'     => ['count' => 0, 'amount'  => 0],
            'annulled' => ['count' => 0, 'amount'  => 0],
        ];

        foreach ($summaryInvoices as $invoice) {
            $debt = $invoice->calculateDebt();

            $summary['total']['count']++;
            $summary['total']['amount'] += $debt['total_invoice'];

            if ($debt['is_annulled']) {
                $summary['annulled']['count']++;
                $summary['annulled']['amount'] += $debt['total_invoice'];
            } elseif ($debt['status'] === 'pending') {
                $summary['pending']['count']++;
                $summary['pending']['amount'] += $debt['owed'];
            } elseif ($debt['status'] === 'partial') {
                $summary['partial']['count']++;
                $summary['partial']['balance'] += $debt['balance'];
            } else { // paid
                $summary['paid']['count']++;
                $summary['paid']['amount'] += $debt['paid_via_expense_report'] ? $debt['owed'] : $debt['total_paid'];
            }
        }


        // Filtro por estado de pago usando HAVING (sobre los subqueries).
        // owed = total_invoice - inv_credit_adj + debit_adj (monto real a pagar), redondeado igual que
        // Invoice::calculateDebt() para que el estado coincida con el que se muestra en la tabla.
        if ($paymentStatus === 'pending') {
            $query->havingRaw('annulment_count = 0 AND expense_report_id IS NULL AND total_paid = 0 AND ROUND(total_invoice - inv_credit_adj + debit_adj) > 0');
        } elseif ($paymentStatus === 'paid') {
            $query->havingRaw('annulment_count = 0 AND (ROUND(total_invoice - inv_credit_adj + debit_adj) <= 0 OR total_paid >= ROUND(total_invoice - inv_credit_adj + debit_adj) OR expense_report_id IS NOT NULL)');
        } elseif ($paymentStatus === 'partial') {
            $query->havingRaw('annulment_count = 0 AND expense_report_id IS NULL AND total_paid > 0 AND total_paid < ROUND(total_invoice - inv_credit_adj + debit_adj)');
        } elseif ($paymentStatus === 'annulled') {
            $query->havingRaw('annulment_count > 0');
        }

        $invoices = $query->orderByDesc('invoices.date')
            ->paginate(50)
            ->through(function ($invoice) {
                $debt = $invoice->calculateDebt((float) $invoice->total_neto, (float) $invoice->total_paid);

                $totalNeto    = $debt['total_neto'];
                $iva          = $debt['iva'];
                $totalInvoice = $debt['total_invoice'];
                $totalPaid    = $debt['total_paid'];
                $balance      = $debt['balance'];
                $status       = $debt['status'];
                $isAnnulled   = $debt['is_annulled'];
                $paidViaExpenseReport = $debt['paid_via_expense_report'];
                $creditTotal  = $debt['credit_total'];
                $debitTotal   = $debt['debit_total'];
                $notes        = $debt['notes'];

                return [
                    'id'              => $invoice->id,
                    'number_document' => $invoice->number_document,
                    'date'            => $invoice->date,
                    'due_date'        => $invoice->due_date,
                    'supplier'        => $invoice->supplier
                        ? ['id' => $invoice->supplier->id, 'name' => $invoice->supplier->name, 'rut' => $invoice->supplier->rut]
                        : null,
                    'company_reason'  => $invoice->companyReason?->name,
                    'type_document'   => $invoice->typeDocument?->name,
                    'total_neto'      => $totalNeto,
                    'iva'             => $iva,
                    'total_invoice'   => $totalInvoice,
                    'total_paid'      => $totalPaid,
                    'balance'         => $balance,
                    'payment_status'  => $status,
                    'is_annulled'     => $isAnnulled,
                    'paid_via_expense_report' => $paidViaExpenseReport,
                    'expense_report'  => $invoice->expenseReport
                        ? ['id' => $invoice->expenseReport->id, 'number' => $invoice->expenseReport->number]
                        : null,
                    'has_notes'       => $notes->isNotEmpty(),
                    'credit_total'    => $creditTotal,
                    'debit_total'     => $debitTotal,
                    'notes'           => $notes,
                    'bank_accounts'   => ($invoice->supplier?->bankAccounts ?? collect())->map(fn($acc) => [
                        'value' => $acc->id,
                        'label' => trim(($acc->bank?->name ?? '').' • '.($acc->accountType?->name ?? '').' • '.$acc->account_number),
                    ])->values(),
                    'payments'        => $invoice->payments->map(fn($p) => [
                        'id'                  => $p->id,
                        'payment_date'        => $p->payment_date,
                        'amount'              => $p->amount,
                        'payment_method'      => $p->payment_method,
                        'payment_method_name' => $p->payment_method_name,
                        'bank_id'             => $p->bank_id,
                        'bank'                => $p->bank?->name,
                        'supplier_bank_account_id' => $p->supplier_bank_account_id,
                        'supplier_bank_account'    => $p->supplierBankAccount
                            ? trim(($p->supplierBankAccount->bank?->name ?? '').' • '.($p->supplierBankAccount->accountType?->name ?? '').' • '.$p->supplierBankAccount->account_number)
                            : null,
                        'supplier_bank_account_bank'   => $p->supplierBankAccount?->bank?->name,
                        'supplier_bank_account_type'   => $p->supplierBankAccount?->accountType?->name,
                        'supplier_bank_account_number' => $p->supplierBankAccount?->account_number,
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
            'summary'   => $summary,
            'filters'   => [
                'term'           => $term,
                'date_from'      => $dateFrom,
                'date_to'        => $dateTo,
                'supplier_id'    => $supplierId,
                'payment_status' => $paymentStatus,
                'payment_type'   => $paymentType,
            ],
        ]);
    }

    // Método para buscar facturas (API endpoint)
    public function searchInvoices(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $query = Invoice::with(['supplier', 'supplier.bankAccounts.bank:id,name', 'supplier.bankAccounts.accountType:id,name', 'typeDocument', 'companyReason', 'invoiceProducts', 'creditDebitNotes.items'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            // No permitir pagar facturas anuladas por nota de crédito
            ->whereDoesntHave('creditDebitNotes', fn($q) => $q->where('is_annulment', 1))
            // No permitir pagar facturas ya cubiertas por una rendición de gastos
            ->whereNull('expense_report_id');

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
            $totalInvoice = round($totalNeto + $iva);

            $totalPaid = $invoice->payments()->sum('amount');

            // Ajuste del saldo por notas (mismo criterio que el índice):
            // NC de inventario resta, nota de débito suma. NC financiera ya está en el precio.
            $inventoryCreditAdj = $invoice->creditDebitNotes
                ->filter(fn($n) => $n->type === 'credito' && $n->affects_inventory && !$n->is_annulment)
                ->sum(fn($n) => $n->items->sum(fn($it) => $it->quantity * $it->unit_price) * 1.19);
            $debitAdj = $invoice->creditDebitNotes
                ->filter(fn($n) => $n->type === 'debito' && !$n->is_annulment)
                ->sum(fn($n) => $n->items->sum(fn($it) => $it->quantity * $it->unit_price) * 1.19);

            $owed = max(0, round($totalInvoice - $inventoryCreditAdj + $debitAdj));
            $balance = max(0, round($owed - $totalPaid));

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
                'payment_status' => ($owed <= 0 || $totalPaid >= $owed) ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending'),
                'bank_accounts' => $invoice->supplier?->bankAccounts->map(fn($acc) => [
                    'value' => $acc->id,
                    'label' => trim(($acc->bank?->name ?? '').' • '.($acc->accountType?->name ?? '').' • '.$acc->account_number),
                ])->values() ?? [],
            ];
        });

        return response()->json($invoices);
    }
}
