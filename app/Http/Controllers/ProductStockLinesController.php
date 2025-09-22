<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class ProductStockLinesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $season_id = session('season_id');
            $product_id = $request->input('product_id');
            if (!$product_id) {
                return response()->json(['error' => 'product_id requerido'], 400);
            }

            // Facturas: obtener líneas base
            $invoiceLines = DB::table('invoice_product')
                ->join('invoices', 'invoice_product.invoice_id', '=', 'invoices.id')
                ->leftJoin('suppliers', 'invoices.supplier_id', '=', 'suppliers.id')
                ->where('invoices.team_id', $user->team_id)
                ->where('invoices.season_id', $season_id)
                ->where('invoice_product.product_id', $product_id)
                ->select(
                    'invoice_product.id as line_id',
                    'invoices.number_document as documento',
                    'suppliers.name as proveedor',
                    'invoice_product.amount as cantidad_original'
                )
                ->get();

            // Notas de débito: obtener líneas base
            $debitNoteLines = DB::table('credit_debit_note_items')
                ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                ->leftJoin('suppliers', 'credit_debit_notes.supplier_id', '=', 'suppliers.id')
                ->where('credit_debit_notes.team_id', $user->team_id)
                ->where('credit_debit_notes.season_id', $season_id)
                ->where('credit_debit_notes.type', 'debito')
                ->where('credit_debit_note_items.product_id', $product_id)
                ->select(
                    'credit_debit_note_items.id as line_id',
                    'credit_debit_notes.number as documento',
                    'suppliers.name as proveedor',
                    'credit_debit_note_items.quantity as cantidad_original'
                )
                ->get();

            // Subconsultas para consumido y devuelto
            $outflowsByInvoiceProduct = DB::table('outflows')
                ->select('invoice_product_id', DB::raw('SUM(quantity) as total_consumido'))
                ->where('team_id', $user->team_id)
                ->where('season_id', $season_id)
                ->whereNotNull('invoice_product_id')
                ->groupBy('invoice_product_id')
                ->pluck('total_consumido', 'invoice_product_id');

            $creditNotesReturns = DB::table('credit_debit_note_items')
                ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                ->where('credit_debit_notes.team_id', $user->team_id)
                ->where('credit_debit_notes.season_id', $season_id)
                ->where('credit_debit_notes.type', 'credito')
                ->whereNotNull('credit_debit_note_items.invoice_product_id')
                ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
                ->groupBy('credit_debit_note_items.invoice_product_id')
                ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');

            $outflowsByDebitNoteItem = DB::table('outflows')
                ->select('credit_debit_note_item_id', DB::raw('SUM(quantity) as total_consumido'))
                ->where('team_id', $user->team_id)
                ->where('season_id', $season_id)
                ->whereNotNull('credit_debit_note_item_id')
                ->groupBy('credit_debit_note_item_id')
                ->pluck('total_consumido', 'credit_debit_note_item_id');

            // Calcular stock disponible por línea
            $result = collect();
            foreach ($invoiceLines as $line) {
                $consumido = $outflowsByInvoiceProduct[$line->line_id] ?? 0;
                $devuelto = $creditNotesReturns[$line->line_id] ?? 0;
                $stock = ($line->cantidad_original ?? 0) - $consumido - $devuelto;
                if ($stock > 0) {
                    $result->push([
                        'line_id' => $line->line_id,
                        'documento' => $line->documento,
                        'proveedor' => $line->proveedor,
                        'cantidad_original' => $line->cantidad_original,
                        'stock_disponible' => $stock,
                        'tipo' => 'factura',
                    ]);
                }
            }
            foreach ($debitNoteLines as $line) {
                $consumido = $outflowsByDebitNoteItem[$line->line_id] ?? 0;
                $stock = ($line->cantidad_original ?? 0) - $consumido;
                if ($stock > 0) {
                    $result->push([
                        'line_id' => $line->line_id,
                        'documento' => $line->documento,
                        'proveedor' => $line->proveedor,
                        'cantidad_original' => $line->cantidad_original,
                        'stock_disponible' => $stock,
                        'tipo' => 'nota_debito',
                    ]);
                }
            }
            return response()->json(['lines' => $result->values()]);
        } catch (\Throwable $e) {
            Log::error('Error en ProductStockLinesController: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }
}
