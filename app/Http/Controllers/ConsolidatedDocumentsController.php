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
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $invoices = Invoice::with(['supplier', 'companyReason', 'typeDocument'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get()
            ->map(function ($invoice) use ($meses) {
                $monto = DB::table('invoice_products')
                    ->where('invoice_id', $invoice->id)
                    ->select(DB::raw('SUM(amount * unit_price) as total'))
                    ->value('total');
                $mes_num = (int)date('n', strtotime($invoice->date));
                $mes_texto = $meses[$mes_num] ?? '';
                $tipo_doc = $invoice->typeDocument->name ?? '';
                $monto_total = $monto ?? 0;
                // Calcular IVA solo para facturas (19% del monto total)
                $iva = (strtolower($tipo_doc) === 'factura') ? ($monto_total * 0.19) : null;
                
                return [
                    'tipo' => $tipo_doc,
                    'razon_social' => $invoice->companyReason->name ?? '',
                    'mes_contable' => $mes_texto,
                    'fecha' => date('d-m-Y', strtotime($invoice->date)),
                    'proveedor' => $invoice->supplier->name ?? '',
                    'n_doc' => $invoice->number_document,
                    'monto_total' => $monto_total,
                    'iva' => $iva,
                ];
            });

        // Notas de crédito/débito
        $notes = CreditDebitNote::with(['supplier', 'invoice.companyReason'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get()
            ->map(function ($note) use ($meses) {
                $monto = DB::table('credit_debit_note_items')
                    ->where('credit_debit_note_id', $note->id)
                    ->select(DB::raw('SUM(quantity * unit_price) as total'))
                    ->value('total');
                // Normalizar tipo para visualización
                $tipo = $note->type;
                $es_credito = false;
                if (in_array($tipo, ['ND', 'debito'])) {
                    $tipo = 'Débito';
                } elseif (in_array($tipo, ['NC', 'credito'])) {
                    $tipo = 'Crédito';
                    $es_credito = true;
                }
                $mes_num = (int)date('n', strtotime($note->date));
                $mes_texto = $meses[$mes_num] ?? '';
                $monto_total = $monto ?? 0;
                // Calcular IVA (19% del monto total)
                $iva = $monto_total * 0.19;
                
                // Si es nota de crédito, el IVA también debe ser negativo
                if ($es_credito) {
                    $iva = -$iva;
                }

                // NC financiera: affects_inventory=0 y tipo crédito → ya descontada del precio
                $isFinancial = !$note->affects_inventory && $es_credito;
                
                return [
                    'tipo' => $tipo,
                    'razon_social' => $note->invoice->companyReason->name ?? '',
                    'mes_contable' => $mes_texto,
                    'fecha' => date('d-m-Y', strtotime($note->date)),
                    'proveedor' => $note->supplier->name ?? '',
                    'n_doc' => $note->number,
                    'monto_total' => $monto_total,
                    'iva' => $iva,
                    'is_financial' => $isFinancial,
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
