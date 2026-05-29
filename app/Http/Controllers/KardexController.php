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
        $branch_id = $request->input('branch_id');
    $product = Product::with('unit')->findOrFail($product_id);

        // Movimientos de facturas (entradas)
        $facturas = DB::table('invoice_products')
            ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('suppliers', 'invoices.supplier_id', '=', 'suppliers.id')
            ->leftJoin('type_documents', 'invoices.type_document_id', '=', 'type_documents.id')
            ->where('invoice_products.product_id', $product_id)
            ->where('invoices.team_id', $user->team_id)
            ->where('invoices.season_id', $season_id)
            ->when($branch_id, fn($q) => $q->where('invoice_products.branch_id', $branch_id))
            ->select([
                'invoices.date as fecha',
                DB::raw("COALESCE(type_documents.name, 'Factura') as tipo"),
                'suppliers.name as proveedor',
                'invoices.number_document as documento',
                'invoice_products.amount as entrada',
                DB::raw('0 as salida'),
                'invoice_products.unit_price as precio',
                DB::raw('NULL as observaciones'),
                DB::raw('1 as affects_inventory')
            ]);

        // Movimientos de notas de débito (entradas)
        $notasDebito = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('suppliers', 'credit_debit_notes.supplier_id', '=', 'suppliers.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'debito')
            ->where('credit_debit_notes.affects_inventory', 1) // Solo los que afectan inventario
            ->when($branch_id, fn($q) => $q->where('credit_debit_note_items.branch_id', $branch_id))
            ->select([
                'credit_debit_notes.date as fecha',
                DB::raw("'Nota Débito' as tipo"),
                'suppliers.name as proveedor',
                'credit_debit_notes.number as documento',
                'credit_debit_note_items.quantity as entrada',
                DB::raw('0 as salida'),
                'credit_debit_note_items.unit_price as precio',
                DB::raw('NULL as observaciones'),
                'credit_debit_notes.affects_inventory'
            ]);

        // Movimientos de notas de crédito (salidas)
        $notasCredito = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('suppliers', 'credit_debit_notes.supplier_id', '=', 'suppliers.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1) // Solo los que afectan inventario
            ->when($branch_id, fn($q) => $q->where('credit_debit_note_items.branch_id', $branch_id))
            ->select([
                'credit_debit_notes.date as fecha',
                DB::raw("'Nota Crédito' as tipo"),
                'suppliers.name as proveedor',
                'credit_debit_notes.number as documento',
                DB::raw('0 as entrada'),
                'credit_debit_note_items.quantity as salida',
                'credit_debit_note_items.unit_price as precio',
                DB::raw('NULL as observaciones'),
                'credit_debit_notes.affects_inventory'
            ]);

        // Movimientos de consumos/outflows (salidas) asociados a factura
    $outflowsFactura = DB::table('outflows')
            ->join('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('suppliers', 'invoices.supplier_id', '=', 'suppliers.id')
            ->where('invoice_products.product_id', $product_id)
            ->where('outflows.team_id', $user->team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.invoice_product_id')
            ->when($branch_id, fn($q) => $q->where('invoice_products.branch_id', $branch_id))
            ->select([
                'outflows.date as fecha',
                DB::raw("'Consumo' as tipo"),
                'suppliers.name as proveedor',
                'invoices.number_document as documento',
                DB::raw('0 as entrada'),
                'outflows.quantity as salida',
                DB::raw('NULL as precio'),
                'outflows.notes as observaciones',
                DB::raw('1 as affects_inventory')
            ]);

        // Movimientos de consumos/outflows (salidas) asociados a nota de débito
    $outflowsND = DB::table('outflows')
            ->join('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('suppliers', 'credit_debit_notes.supplier_id', '=', 'suppliers.id')
            ->where('credit_debit_note_items.product_id', $product_id)
            ->where('outflows.team_id', $user->team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.credit_debit_note_item_id')
            ->when($branch_id, fn($q) => $q->where('credit_debit_note_items.branch_id', $branch_id))
            ->select([
                'outflows.date as fecha',
                DB::raw("'Consumo ND' as tipo"),
                'suppliers.name as proveedor',
                'credit_debit_notes.number as documento',
                DB::raw('0 as entrada'),
                'outflows.quantity as salida',
                DB::raw('NULL as precio'),
                'outflows.notes as observaciones',
                DB::raw('1 as affects_inventory')
            ]);

        // Unir todos los movimientos y recuperar resultados sin ORDER BY
        $movimientos = $facturas
            ->unionAll($notasDebito)
            ->unionAll($notasCredito)
            ->unionAll($outflowsFactura)
            ->unionAll($outflowsND)
            ->get();
        // Ordenar por fecha en PHP para evitar problemas con ORDER BY en consultas UNION
    $movimientos = collect($movimientos)->sortBy('fecha')->values()->all();

        // Calcular saldo acumulado
    // Calcular saldo acumulado
    /** @var \stdClass[] $movimientos */
    $saldo = 0;
    $kardex = [];
    /** @var \stdClass $mov */
    foreach ($movimientos as $mov) {
            $saldo += ($mov->entrada - $mov->salida);
            $kardex[] = [
                'fecha' => $mov->fecha,
                'tipo' => $mov->tipo,
                'proveedor' => $mov->proveedor ?? null,
                'documento' => $mov->documento,
                'entrada' => $mov->entrada,
                'salida' => $mov->salida,
                'saldo' => $saldo,
                'precio' => $mov->precio,
                'observaciones' => $mov->observaciones,
                'affects_inventory' => $mov->affects_inventory,
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
