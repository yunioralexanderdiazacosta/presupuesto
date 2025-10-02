<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\CreditDebitNote;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConsolidatedDocumentsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Facturas
        // Facturas
        $invoices = Invoice::with(['supplier', 'companyReason', 'typeDocument'])
            ->get()
            ->map(function ($invoice) {
                $monto = DB::table('invoice_product')
                    ->where('invoice_id', $invoice->id)
                    ->select(DB::raw('SUM(amount * unit_price) as total'))
                    ->value('total');
                return [
                    'tipo' => $invoice->typeDocument->name ?? '',
                    'razon_social' => $invoice->companyReason->name ?? '',
                    'mes_contable' => date('Y-m', strtotime($invoice->date)),
                    'fecha' => date('d-m-Y', strtotime($invoice->date)),
                    'proveedor' => $invoice->supplier->name ?? '',
                    'n_doc' => $invoice->number_document,
                    'monto_total' => $monto ?? 0,
                ];
            });

        // Notas de crédito/débito
        $notes = CreditDebitNote::with(['supplier', 'invoice.companyReason'])
            ->get()
            ->map(function ($note) {
                $monto = DB::table('credit_debit_note_items')
                    ->where('credit_debit_note_id', $note->id)
                    ->select(DB::raw('SUM(quantity * unit_price) as total'))
                    ->value('total');
                return [
                    'tipo' => $note->type,
                    'razon_social' => $note->invoice->companyReason->name ?? '',
                    'mes_contable' => date('Y-m', strtotime($note->date)),
                    'fecha' => date('d-m-Y', strtotime($note->date)),
                    'proveedor' => $note->supplier->name ?? '',
                    'n_doc' => $note->number,
                    'monto_total' => $monto ?? 0,
                ];
            });

        // Unir ambos
        $consolidated = $invoices->concat($notes)->values();

        return Inertia::render('ConsolidatedDocuments', [
            'documents' => $consolidated,
        ]);

        // Notas de crédito
        $creditNotesCount = CreditDebitNote::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->where('type', 'NC')
            ->count();

        // Notas de débito
        $debitNotesCount = CreditDebitNote::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->where('type', 'ND')
            ->count();

        return Inertia::render('ConsolidatedDocuments', [
            'invoicesCount' => $invoicesCount,
            'creditNotesCount' => $creditNotesCount,
            'debitNotesCount' => $debitNotesCount,
        ]);
    }
}
