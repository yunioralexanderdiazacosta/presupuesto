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
    $entradas = DB::table('invoice_products')
            ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_products.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
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
                'units.name as unit_name',
                DB::raw('SUM(invoice_products.amount) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Notas de débito (tipo = "debito")
        $debitNotes = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'debito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                'units.name as unit_name',
                DB::raw('SUM(credit_debit_note_items.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

    // Salidas: Outflows (factura y nota de débito) + Notas de crédito
        // Salidas asociadas a factura
        $salidasFactura = DB::table('outflows')
            ->join('invoice_products', 'outflows.invoice_product_id', '=', 'invoice_products.id')
            ->join('products', 'invoice_products.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('outflows.team_id', $team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.invoice_product_id')
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                'units.name as unit_name',
                DB::raw('SUM(outflows.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Salidas asociadas a nota de débito
        $salidasND = DB::table('outflows')
            ->join('credit_debit_note_items', 'outflows.credit_debit_note_item_id', '=', 'credit_debit_note_items.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('outflows.team_id', $team_id)
            ->where('outflows.season_id', $season_id)
            ->whereNotNull('outflows.credit_debit_note_item_id')
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                'units.name as unit_name',
                DB::raw('SUM(outflows.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Notas de crédito (tipo = "credito")
        $creditNotes = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->select(
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'products.id as product_id',
                'products.name as product_name',
                'units.name as unit_name',
                DB::raw('SUM(credit_debit_note_items.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Unir y calcular inventario
    $entradasArr = $entradas->get()->toArray();
    $debitArr = $debitNotes->get()->toArray();
    $salidasArr = array_merge($salidasFactura->get()->toArray(), $salidasND->get()->toArray());
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
                        'unit_name' => property_exists($row, 'unit_name') ? $row->unit_name : null,
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

    /**
     * Calcula el stock disponible agrupado por producto e invoice_product.
     * Retorna un array con invoice_products que tienen stock disponible,
     * agrupados por product_id.
     * 
     * @param int $teamId
     * @param int $seasonId
     * @param int|null $excludeOutflowId ID del outflow a excluir del cálculo (útil para edición)
     * @return array
     */
    public function getAvailableStocksByInvoiceProduct($teamId, $seasonId, $excludeOutflowId = null)
    {
        // Calcular consumos por invoice_product_id desde OUTFLOWS (tabla maestra del kardex)
        $consumosByInvoiceProduct = DB::table('outflows')
            ->select('invoice_product_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->whereNotNull('invoice_product_id')
            ->when($excludeOutflowId, function($q) use ($excludeOutflowId) {
                $q->where('id', '!=', $excludeOutflowId);
            })
            ->groupBy('invoice_product_id')
            ->pluck('total_consumido', 'invoice_product_id');

        // Devoluciones (notas de crédito que afectan inventario)
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $teamId)
            ->where('credit_debit_notes.season_id', $seasonId)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');

        // Precalcular NC financieras (affects_inventory=0) por invoice_product
        $financialNCsByIP = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $teamId)
            ->where('credit_debit_notes.season_id', $seasonId)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 0)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity * credit_debit_note_items.unit_price) as nc_total'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('nc_total', 'credit_debit_note_items.invoice_product_id');

        // Traer facturas con productos
        $stocksByProduct = [];

        $invoices = \App\Models\Invoice::with(['supplier', 'typeDocument', 'products.unit'])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();

        foreach ($invoices as $invoice) {
            foreach ($invoice->products as $product) {
                // Excluir si tiene nota de crédito de anulación que afecta inventario
                $hasAnnulmentNote = DB::table('credit_debit_note_items')
                    ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                    ->where('credit_debit_notes.type', 'credito')
                    ->where('credit_debit_notes.is_annulment', 1)
                    ->where('credit_debit_notes.affects_inventory', 1)
                    ->where('credit_debit_note_items.invoice_product_id', $product->pivot->id)
                    ->exists();

                if ($hasAnnulmentNote) {
                    continue;
                }

                $consumido = $consumosByInvoiceProduct[$product->pivot->id] ?? 0;
                $devuelto = $creditNotesReturns[$product->pivot->id] ?? 0;
                $cantidadOriginal = $product->pivot->amount ?? 0;
                $stockDisponible = round($cantidadOriginal - $consumido - $devuelto, 2);

                if ($stockDisponible <= 0) {
                    continue;
                }

                // Agrupar por product_id
                if (!isset($stocksByProduct[$product->id])) {
                    $stocksByProduct[$product->id] = [];
                }

                $unitPrice = $product->pivot->unit_price ?? 0;
                $ncFinanciero = $financialNCsByIP[$product->pivot->id] ?? 0;
                $effectiveUnitPrice = $cantidadOriginal > 0
                    ? round($unitPrice - ($ncFinanciero / $cantidadOriginal), 2)
                    : $unitPrice;

                $stocksByProduct[$product->id][] = [
                    'invoice_product_id' => $product->pivot->id,
                    'number_document' => $invoice->number_document,
                    'supplier' => $invoice->supplier->name ?? '-',
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit' => $product->unit->name ?? '-',
                    'unit_price' => $unitPrice,
                    'effective_unit_price' => $effectiveUnitPrice,
                    'date' => $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('Y-m-d') : $invoice->date,
                ];
            }
        }

        return $stocksByProduct;
    }
}
