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
            ->leftJoin('branches', 'invoice_products.branch_id', '=', 'branches.id')
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
                'invoice_products.branch_id',
                'branches.name as branch_name',
                DB::raw('SUM(invoice_products.amount) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name', 'invoice_products.branch_id', 'branches.name');

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
                DB::raw('NULL as branch_id'),
                DB::raw('NULL as branch_name'),
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
            ->leftJoin('branches', 'invoice_products.branch_id', '=', 'branches.id')
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
                'invoice_products.branch_id',
                'branches.name as branch_name',
                DB::raw('SUM(outflows.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name', 'invoice_products.branch_id', 'branches.name');

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
                DB::raw('NULL as branch_id'),
                DB::raw('NULL as branch_name'),
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
                DB::raw('NULL as branch_id'),
                DB::raw('NULL as branch_name'),
                DB::raw('SUM(credit_debit_note_items.quantity) as cantidad')
            )
            ->groupBy('products.level2_id', 'level2s.name', 'products.level3_id', 'level3s.name', 'products.id', 'products.name');

        // Unir y calcular inventario
    $entradasArr = $entradas->get()->toArray();
    $debitArr = $debitNotes->get()->toArray();
    $salidasArr = array_merge($salidasFactura->get()->toArray(), $salidasND->get()->toArray());
    $creditArr = $creditNotes->get()->toArray();

        // Agrupar por nivel2, nivel3, producto, sucursal
        $inventario = [];
    foreach ([$entradasArr, $debitArr] as $arr) {
            foreach ($arr as $row) {
                $key = $row->level2_id.'-'.$row->level3_id.'-'.$row->product_id.'-'.($row->branch_id ?? 'null');
                if (!isset($inventario[$key])) {
                    $inventario[$key] = [
                        'level2_id' => $row->level2_id,
                        'level2_name' => property_exists($row, 'level2_name') ? $row->level2_name : null,
                        'level3_id' => $row->level3_id,
                        'level3_name' => property_exists($row, 'level3_name') ? $row->level3_name : null,
                        'product_id' => $row->product_id,
                        'product_name' => $row->product_name,
                        'unit_name' => property_exists($row, 'unit_name') ? $row->unit_name : null,
                        'branch_id' => $row->branch_id ?? null,
                        'branch_name' => $row->branch_name ?? null,
                        'cantidad' => 0
                    ];
                }
                $inventario[$key]['cantidad'] += $row->cantidad;
            }
        }
    foreach ([$salidasArr, $creditArr] as $arr) {
            foreach ($arr as $row) {
                $key = $row->level2_id.'-'.$row->level3_id.'-'.$row->product_id.'-'.($row->branch_id ?? 'null');
                if (!isset($inventario[$key])) {
                    $inventario[$key] = [
                        'level2_id' => $row->level2_id,
                        'level2_name' => property_exists($row, 'level2_name') ? $row->level2_name : null,
                        'level3_id' => $row->level3_id,
                        'level3_name' => property_exists($row, 'level3_name') ? $row->level3_name : null,
                        'product_id' => $row->product_id,
                        'product_name' => $row->product_name,
                        'branch_id' => $row->branch_id ?? null,
                        'branch_name' => $row->branch_name ?? null,
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

        $branches = \App\Models\Branch::pluck('name', 'id');

        $invoices = \App\Models\Invoice::with(['supplier', 'companyReason', 'typeDocument', 'products.unit'])
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
                    'company_reason' => $invoice->companyReason->name ?? null,
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit' => $product->unit->name ?? '-',
                    'unit_price' => $unitPrice,
                    'effective_unit_price' => $effectiveUnitPrice,
                    'date' => $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('Y-m-d') : $invoice->date,
                    'branch_id' => $product->pivot->branch_id,
                    'branch_name' => $product->pivot->branch_id ? ($branches[$product->pivot->branch_id] ?? null) : null,
                ];
            }
        }

        return $stocksByProduct;
    }

    /**
     * Calcula el inventario valorizado (stock * precio de costo) agrupado por nivel2, nivel3, producto y sucursal.
     * El stock y precio se calculan a nivel de lote (línea de factura o item de nota de débito), igual que
     * getAvailableStocksByInvoiceProduct, para reflejar el precio real de costo de cada lote consumido/restante.
     */
    public function getValorizedInventory($team_id, $season_id)
    {
        // Consumos de outflows por lote (factura o nota de débito)
        $outflowsByInvoiceProduct = DB::table('outflows')
            ->select('invoice_product_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('invoice_product_id')
            ->groupBy('invoice_product_id')
            ->pluck('total_consumido', 'invoice_product_id');

        $outflowsByDebitNoteItem = DB::table('outflows')
            ->select('credit_debit_note_item_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('credit_debit_note_item_id')
            ->groupBy('credit_debit_note_item_id')
            ->pluck('total_consumido', 'credit_debit_note_item_id');

        // Devoluciones (notas de crédito que afectan inventario) sobre líneas de factura
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');

        // Notas de crédito financieras (no afectan inventario, solo ajustan el costo de la línea)
        $financialNCsByIP = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 0)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity * credit_debit_note_items.unit_price) as nc_total'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('nc_total', 'credit_debit_note_items.invoice_product_id');

        $result = [];

        // --- Lotes de factura ---
        $invoiceProducts = DB::table('invoice_products')
            ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_products.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
            ->leftJoin('branches', 'invoice_products.branch_id', '=', 'branches.id')
            ->where('invoices.team_id', $team_id)
            ->where('invoices.season_id', $season_id)
            ->select(
                'invoice_products.id',
                'invoice_products.amount',
                'invoice_products.unit_price',
                'invoice_products.branch_id',
                'branches.name as branch_name',
                'products.id as product_id',
                'products.name as product_name',
                'level2s.level1_id',
                'level1s.name as level1_name',
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'units.name as unit_name'
            )
            ->get();

        foreach ($invoiceProducts as $ip) {
            $consumido = $outflowsByInvoiceProduct[$ip->id] ?? 0;
            $devuelto = $creditNotesReturns[$ip->id] ?? 0;
            $cantidadOriginal = $ip->amount ?? 0;
            $stockDisponible = round($cantidadOriginal - $consumido - $devuelto, 2);

            if ($stockDisponible <= 0) {
                continue;
            }

            $ncFinanciero = $financialNCsByIP[$ip->id] ?? 0;
            $effectivePrice = $cantidadOriginal > 0
                ? round($ip->unit_price - ($ncFinanciero / $cantidadOriginal), 2)
                : $ip->unit_price;

            $key = $ip->product_id . '-' . ($ip->branch_id ?? 'null');
            if (!isset($result[$key])) {
                $result[$key] = [
                    'level1_id' => $ip->level1_id,
                    'level1_name' => $ip->level1_name,
                    'level2_id' => $ip->level2_id,
                    'level2_name' => $ip->level2_name,
                    'level3_id' => $ip->level3_id,
                    'level3_name' => $ip->level3_name,
                    'product_id' => $ip->product_id,
                    'product_name' => $ip->product_name,
                    'unit_name' => $ip->unit_name,
                    'branch_id' => $ip->branch_id,
                    'branch_name' => $ip->branch_name,
                    'cantidad' => 0,
                    'valor' => 0,
                ];
            }
            $result[$key]['cantidad'] += $stockDisponible;
            $result[$key]['valor'] += $stockDisponible * $effectivePrice;
        }

        // --- Lotes de nota de débito (compras de inventario sin factura) ---
        $debitItems = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->join('products', 'credit_debit_note_items.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('level2s', 'products.level2_id', '=', 'level2s.id')
            ->leftJoin('level3s', 'products.level3_id', '=', 'level3s.id')
            ->leftJoin('level1s', 'level2s.level1_id', '=', 'level1s.id')
            ->leftJoin('branches', 'credit_debit_note_items.branch_id', '=', 'branches.id')
            ->where('credit_debit_notes.team_id', $team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'debito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->select(
                'credit_debit_note_items.id',
                'credit_debit_note_items.quantity',
                'credit_debit_note_items.unit_price',
                'credit_debit_note_items.branch_id',
                'branches.name as branch_name',
                'level2s.level1_id',
                'level1s.name as level1_name',
                'products.id as product_id',
                'products.name as product_name',
                'products.level2_id',
                'level2s.name as level2_name',
                'products.level3_id',
                'level3s.name as level3_name',
                'units.name as unit_name'
            )
            ->get();

        foreach ($debitItems as $item) {
            $consumido = $outflowsByDebitNoteItem[$item->id] ?? 0;
            $stockDisponible = round($item->quantity - $consumido, 2);

            if ($stockDisponible <= 0) {
                continue;
            }

            $key = $item->product_id . '-' . ($item->branch_id ?? 'null');
            if (!isset($result[$key])) {
                $result[$key] = [
                    'level1_id' => $item->level1_id,
                    'level1_name' => $item->level1_name,
                    'level2_id' => $item->level2_id,
                    'level2_name' => $item->level2_name,
                    'level3_id' => $item->level3_id,
                    'level3_name' => $item->level3_name,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'unit_name' => $item->unit_name,
                    'branch_id' => $item->branch_id,
                    'branch_name' => $item->branch_name,
                    'cantidad' => 0,
                    'valor' => 0,
                ];
            }
            $result[$key]['cantidad'] += $stockDisponible;
            $result[$key]['valor'] += $stockDisponible * $item->unit_price;
        }

        return array_values(array_map(function ($row) {
            $row['cantidad'] = round($row['cantidad'], 2);
            $row['valor'] = round($row['valor'], 2);
            $row['precio_promedio'] = $row['cantidad'] > 0 ? round($row['valor'] / $row['cantidad'], 2) : 0;
            return $row;
        }, $result));
    }
}
