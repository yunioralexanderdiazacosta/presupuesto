<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasInventory
{
    /**
     * Calcula el inventario agrupado por nivel2, nivel3 y producto.
     * Devuelve un array con: nivel2_id, nivel3_id, product_id, cantidad, nombre_producto
     */
    public function getInventory($team_id, $season_id)
    {
    // Entradas: Facturas + Notas de débito
    $entradas = DB::table('invoice_product')
            ->join('invoices', 'invoice_product.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_product.product_id', '=', 'products.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('invoices.team_id', $team_id)
            ->where('invoices.season_id', $season_id)
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(invoice_product.amount) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Notas de débito (tipo = "debito")
        $debitNotes = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'debito')
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(credit_debit_note_items.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

    // Salidas: Outflows (relación correcta con producto) + Notas de crédito
        $salidas = DB::table('outflows')
            ->join('invoice_product', 'outflows.invoice_product_id', '=', 'invoice_product.id')
            ->join('products', 'invoice_product.product_id', '=', 'products.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('outflows.team_id', $team_id)
            ->where('outflows.season_id', $season_id)
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(outflows.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Notas de crédito (tipo = "credito")
        $creditNotes = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(credit_debit_note_items.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Unir y calcular inventario
    $entradasArr = $entradas->get()->toArray();
    $debitArr = $debitNotes->get()->toArray();
    $salidasArr = $salidas->get()->toArray();
    $creditArr = $creditNotes->get()->toArray();

        // Agrupar por nivel2, nivel3, producto
        $inventario = [];
    foreach ([$entradasArr, $debitArr] as $arr) {
            foreach ($arr as $row) {
                $key = $row->level2_id.'-'.$row->level3_id.'-'.$row->product_id;
                if (!isset($inventario[$key])) {
                    $inventario[$key] = [
                        'level2_id' => $row->level2_id,
                        'level2_name' => property_exists($row, 'level2_name') ? $row->level2_name : null,
                        'level3_id' => $row->level3_id,
                        'level3_name' => property_exists($row, 'level3_name') ? $row->level3_name : null,
                        'product_id' => $row->product_id,
                        'product_name' => $row->product_name,
                        'cantidad' => 0
                    ];
                }
                $inventario[$key]['cantidad'] += $row->cantidad;
            }
        }
    foreach ([$salidasArr, $creditArr] as $arr) {
            foreach ($arr as $row) {
                $key = $row->level2_id.'-'.$row->level3_id.'-'.$row->product_id;
                if (!isset($inventario[$key])) {
                    $inventario[$key] = [
                        'level2_id' => $row->level2_id,
                        'level2_name' => property_exists($row, 'level2_name') ? $row->level2_name : null,
                        'level3_id' => $row->level3_id,
                        'level3_name' => property_exists($row, 'level3_name') ? $row->level3_name : null,
                        'product_id' => $row->product_id,
                        'product_name' => $row->product_name,
                        'cantidad' => 0
                    ];
                }
                $inventario[$key]['cantidad'] -= $row->cantidad;
            }
        }
        return array_values($inventario);
    }
}
