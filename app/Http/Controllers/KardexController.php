<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class KardexController extends Controller
{
    public function show($product_id, Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
    $product = Product::with('unit')->findOrFail($product_id);

        // Movimientos de facturas (entradas)
        $facturas = DB::table('invoice_product')
            ->join('invoices', 'invoice_product.invoice_id', '=', 'invoices.id')
            ->where('invoice_product.product_id', $product_id)
            ->where('invoices.team_id', $user->team_id)
            ->where('invoices.season_id', $season_id)
            ->select([
                'invoices.date as fecha',
                DB::raw("'Factura' as tipo"),
                'invoices.number_document as documento',
                'invoice_product.amount as entrada',
                DB::raw('0 as salida'),
                'invoice_product.unit_price as precio',
                DB::raw('NULL as observaciones')
            ]);

        // Movimientos de notas de débito (entradas)
        $notasDebito = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'debito')
            ->select([
                'credit_debit_notes.date as fecha',
                DB::raw("'Nota Débito' as tipo"),
                'credit_debit_notes.number as documento',
                'credit_debit_note_items.quantity as entrada',
                DB::raw('0 as salida'),
                'credit_debit_note_items.unit_price as precio',
                DB::raw('NULL as observaciones')
            ]);

        // Movimientos de notas de crédito (salidas)
        $notasCredito = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->select([
                'credit_debit_notes.date as fecha',
                DB::raw("'Nota Crédito' as tipo"),
                'credit_debit_notes.number as documento',
                DB::raw('0 as entrada'),
                'credit_debit_note_items.quantity as salida',
                'credit_debit_note_items.unit_price as precio',
                DB::raw('NULL as observaciones')
            ]);

        // Movimientos de consumos/outflows (salidas) asociados a factura
        $outflowsFactura = DB::table('outflows')
            ->join('invoice_product', 'outflows.invoice_product_id', '=', 'invoice_product.id')
            ->join('invoices', 'invoice_product.invoice_id', '=', 'invoices.id')
            ->where('invoice_product.product_id', $product_id)
            ->where('outflows.team_id', $user->team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.invoice_product_id')
            ->select([
                'outflows.date as fecha',
                DB::raw("'Consumo' as tipo"),
                'invoices.number_document as documento',
                DB::raw('0 as entrada'),
                'outflows.quantity as salida',
                DB::raw('NULL as precio'),
                'outflows.notes as observaciones'
            ]);

        // Movimientos de consumos/outflows (salidas) asociados a nota de débito
        $outflowsND = DB::table('outflows')
            ->join('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('outflows.team_id', $user->team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.credit_debit_note_item_id')
            ->select([
                'outflows.date as fecha',
                DB::raw("'Consumo ND' as tipo"),
                'credit_debit_notes.number as documento',
                DB::raw('0 as entrada'),
                'outflows.quantity as salida',
                DB::raw('NULL as precio'),
                'outflows.notes as observaciones'
            ]);

        // Unir todos los movimientos y ordenarlos por fecha
        $movimientos = $facturas
            ->unionAll($notasDebito)
            ->unionAll($notasCredito)
            ->unionAll($outflowsFactura)
            ->unionAll($outflowsND)
            ->orderBy('fecha')
            ->get();

        // Calcular saldo acumulado
        $saldo = 0;
        $kardex = [];
        foreach ($movimientos as $mov) {
            $saldo += ($mov->entrada - $mov->salida);
            $kardex[] = [
                'fecha' => $mov->fecha,
                'tipo' => $mov->tipo,
                'documento' => $mov->documento,
                'entrada' => $mov->entrada,
                'salida' => $mov->salida,
                'saldo' => $saldo,
                'precio' => $mov->precio,
                'observaciones' => $mov->observaciones,
            ];
        }

        // Si la petición es AJAX (fetch desde el frontend), devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unit' => $product->unit ? $product->unit->name : '',
                ],
                'kardex' => $kardex,
            ]);
        }
        // Si no, renderizar la vista Inertia normal
        return Inertia::render('Kardex/Show', [
            'product' => $product,
            'kardex' => $kardex,
        ]);
    }
}
