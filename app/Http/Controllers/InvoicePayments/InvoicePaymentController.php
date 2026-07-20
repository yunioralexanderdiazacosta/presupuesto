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
        $paymentType   = $request->has('payment_type') ? $request->payment_type : '1'; // Default: Crédito

        // Query base: Facturas del equipo/temporada con totales calculados via subquery
        // total_neto: suma pura de productos (sin IVA)
        // total_invoice: neto × 1.19 para FACTURA/NOTA CREDITO/NOTA DEBITO, neto para el resto
        $query = Invoice::select('invoices.*')
            ->selectRaw('(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) as total_neto')
            ->selectRaw("(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) * CASE WHEN UPPER(td.name) IN ('FACTURA', 'NOTA CREDITO', 'NOTA DEBITO') THEN 1.19 ELSE 1.0 END as total_invoice")
            ->selectRaw('(SELECT COALESCE(SUM(pay.amount), 0) FROM invoice_payments pay WHERE pay.invoice_id = invoices.id) as total_paid')
            ->selectRaw('(SELECT COUNT(*) FROM credit_debit_notes cdn WHERE cdn.invoice_id = invoices.id AND cdn.is_annulment = 1) as annulment_count')
            ->selectRaw("COALESCE((SELECT SUM(cdni.quantity * cdni.unit_price) FROM credit_debit_notes cdn JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id WHERE cdn.invoice_id = invoices.id AND cdn.type = 'credito' AND cdn.affects_inventory = 1 AND cdn.is_annulment = 0), 0) * 1.19 as inv_credit_adj")
            ->selectRaw("COALESCE((SELECT SUM(cdni.quantity * cdni.unit_price) FROM credit_debit_notes cdn JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id WHERE cdn.invoice_id = invoices.id AND cdn.type = 'debito' AND cdn.is_annulment = 0), 0) * 1.19 as debit_adj")
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

        // Resumen por estado (sin filtro de payment_status para mostrar siempre los totales completos)
        $summaryBase = Invoice::select(
                DB::raw("(SELECT COALESCE(SUM(ip.unit_price * ip.amount), 0) FROM invoice_products ip WHERE ip.invoice_id = invoices.id) * CASE WHEN UPPER(td.name) IN ('FACTURA', 'NOTA CREDITO', 'NOTA DEBITO') THEN 1.19 ELSE 1.0 END as total_invoice"),
                DB::raw('(SELECT COALESCE(SUM(pay.amount), 0) FROM invoice_payments pay WHERE pay.invoice_id = invoices.id) as total_paid'),
                DB::raw('(SELECT COUNT(*) FROM credit_debit_notes cdn WHERE cdn.invoice_id = invoices.id AND cdn.is_annulment = 1) as annulment_count'),
                DB::raw("COALESCE((SELECT SUM(cdni.quantity * cdni.unit_price) FROM credit_debit_notes cdn JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id WHERE cdn.invoice_id = invoices.id AND cdn.type = 'credito' AND cdn.affects_inventory = 1 AND cdn.is_annulment = 0), 0) * 1.19 as inv_credit_adj"),
                DB::raw("COALESCE((SELECT SUM(cdni.quantity * cdni.unit_price) FROM credit_debit_notes cdn JOIN credit_debit_note_items cdni ON cdni.credit_debit_note_id = cdn.id WHERE cdn.invoice_id = invoices.id AND cdn.type = 'debito' AND cdn.is_annulment = 0), 0) * 1.19 as debit_adj")
            )
            ->leftJoin('type_documents as td', 'td.id', '=', 'invoices.type_document_id')
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

        $summaryRaw = DB::query()->fromSub($summaryBase, 'sub')->selectRaw("
            COUNT(*) as total_count,
            COALESCE(SUM(total_invoice), 0) as total_amount,
            SUM(CASE WHEN annulment_count = 0 AND total_paid = 0 AND (total_invoice - inv_credit_adj + debit_adj) > 0 THEN 1 ELSE 0 END) as pending_count,
            COALESCE(SUM(CASE WHEN annulment_count = 0 AND total_paid = 0 AND (total_invoice - inv_credit_adj + debit_adj) > 0 THEN (total_invoice - inv_credit_adj + debit_adj) ELSE 0 END), 0) as pending_amount,
            SUM(CASE WHEN annulment_count = 0 AND total_paid > 0 AND total_paid < (total_invoice - inv_credit_adj + debit_adj) THEN 1 ELSE 0 END) as partial_count,
            COALESCE(SUM(CASE WHEN annulment_count = 0 AND total_paid > 0 AND total_paid < (total_invoice - inv_credit_adj + debit_adj) THEN ((total_invoice - inv_credit_adj + debit_adj) - total_paid) ELSE 0 END), 0) as partial_balance,
            SUM(CASE WHEN annulment_count = 0 AND (total_invoice - inv_credit_adj + debit_adj) > 0 AND total_paid >= (total_invoice - inv_credit_adj + debit_adj) THEN 1 ELSE 0 END) as paid_count,
            COALESCE(SUM(CASE WHEN annulment_count = 0 AND (total_invoice - inv_credit_adj + debit_adj) > 0 AND total_paid >= (total_invoice - inv_credit_adj + debit_adj) THEN total_paid ELSE 0 END), 0) as paid_amount,
            SUM(CASE WHEN annulment_count > 0 THEN 1 ELSE 0 END) as annulled_count,
            COALESCE(SUM(CASE WHEN annulment_count > 0 THEN total_invoice ELSE 0 END), 0) as annulled_amount
        ")->first();

        $summary = [
            'total'    => ['count' => (int) $summaryRaw->total_count,    'amount'  => (float) $summaryRaw->total_amount],
            'pending'  => ['count' => (int) $summaryRaw->pending_count,  'amount'  => (float) $summaryRaw->pending_amount],
            'partial'  => ['count' => (int) $summaryRaw->partial_count,  'balance' => (float) $summaryRaw->partial_balance],
            'paid'     => ['count' => (int) $summaryRaw->paid_count,     'amount'  => (float) $summaryRaw->paid_amount],
            'annulled' => ['count' => (int) $summaryRaw->annulled_count, 'amount'  => (float) $summaryRaw->annulled_amount],
        ];

        // Filtro por estado de pago usando HAVING (sobre los subqueries).
        // owed = total_invoice - inv_credit_adj + debit_adj (monto real a pagar)
        if ($paymentStatus === 'pending') {
            $query->havingRaw('annulment_count = 0 AND total_paid = 0 AND (total_invoice - inv_credit_adj + debit_adj) > 0');
        } elseif ($paymentStatus === 'paid') {
            $query->havingRaw('annulment_count = 0 AND ((total_invoice - inv_credit_adj + debit_adj) <= 0 OR total_paid >= (total_invoice - inv_credit_adj + debit_adj))');
        } elseif ($paymentStatus === 'partial') {
            $query->havingRaw('annulment_count = 0 AND total_paid > 0 AND total_paid < (total_invoice - inv_credit_adj + debit_adj)');
        } elseif ($paymentStatus === 'annulled') {
            $query->havingRaw('annulment_count > 0');
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
                $totalInvoice = round($totalNeto + $iva);
                $balance      = round($totalInvoice - $totalPaid);

                // Notas de crédito/débito asociadas a la factura
                $notes = $invoice->creditDebitNotes->map(function ($note) {
                    $netoNota  = $note->items->sum(fn($it) => $it->quantity * $it->unit_price);
                    $totalNota = round($netoNota * 1.19); // Las notas llevan IVA igual que la factura
                    return [
                        'id'                => $note->id,
                        'type'              => $note->type,
                        'number'            => $note->number,
                        'is_annulment'      => (bool) $note->is_annulment,
                        'affects_inventory' => (bool) $note->affects_inventory,
                        'total'             => $totalNota,
                    ];
                })->values();

                $isAnnulled  = $invoice->creditDebitNotes->contains(fn($n) => (bool) $n->is_annulment);
                $creditTotal = $notes->where('type', 'credito')->sum('total');
                $debitTotal  = $notes->where('type', 'debito')->sum('total');

                // Ajuste del saldo por notas NO aplicadas al precio de la factura:
                // - NC financiera (affects_inventory=false): ya está descontada en el precio → NO se resta otra vez.
                // - NC de inventario (affects_inventory=true): sí reduce lo que se paga → se resta.
                // - Nota de débito: aumenta lo que se paga → se suma.
                $inventoryCreditTotal = $notes
                    ->where('type', 'credito')
                    ->where('affects_inventory', true)
                    ->where('is_annulment', false)
                    ->sum('total');

                if ($isAnnulled) {
                    // Una anulación cancela el 100% de la factura: no debe pagarse
                    $status  = 'annulled';
                    $owed    = 0;
                    $balance = 0;
                } else {
                    // Monto real a pagar considerando notas
                    $owed    = max(0, round($totalInvoice - $inventoryCreditTotal + $debitTotal));
                    $balance = max(0, round($owed - $totalPaid));

                    if ($owed <= 0 || $totalPaid >= $owed) {
                        $status = 'paid';
                    } elseif ($totalPaid > 0) {
                        $status = 'partial';
                    } else {
                        $status = 'pending';
                    }
                }

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
            ->whereDoesntHave('creditDebitNotes', fn($q) => $q->where('is_annulment', 1));

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
