<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetComparativeMonthlyDetailController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month_id'            => 'required|integer|between:1,12',
            'include_investments' => 'nullable|boolean',
        ]);

        $user               = Auth::user();
        $team_id            = $user->team_id;
        $season_id          = session('season_id');
        $month_id           = (int) $request->month_id;
        $includeInvestments = filter_var($request->input('include_investments', true), FILTER_VALIDATE_BOOLEAN);

        if (!$season_id) {
            return response()->json(['error' => 'Sin temporada activa'], 422);
        }

        // -------------------------------------------------------
        // 1. FACTURADO del mes: invoice_products por fecha de factura
        // -------------------------------------------------------
        $invoicedRows = DB::table('invoice_products as ip')
            ->join('invoices as i', 'ip.invoice_id', '=', 'i.id')
            ->join('products as p', 'ip.product_id', '=', 'p.id')
            ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('i.team_id', $team_id)
            ->where('i.season_id', $season_id)
            ->whereMonth('i.date', $month_id)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                DB::raw("COALESCE(l3.name, 'Sin clasificar') as level3"),
                DB::raw("COALESCE(l2.name, 'Sin clasificar') as level2"),
                DB::raw("COALESCE(l1.name, 'Sin clasificar') as level1"),
                DB::raw('SUM(ip.unit_price * ip.amount) as total_invoiced')
            )
            ->groupBy('p.id', 'p.name', 'l3.name', 'l2.name', 'l1.name')
            ->get()
            ->keyBy('product_id');

        // -------------------------------------------------------
        // 1b. Ajustes de notas (NC/ND con affects_inventory=1) del mes
        //     Mismo criterio que el gráfico: agrupa por MONTH(cdn.date)
        //     NC resta, ND suma al total facturado del producto
        // -------------------------------------------------------
        $noteAdjustmentsByProduct = [];
        $noteRows = DB::table('credit_debit_note_items as cdni')
            ->join('credit_debit_notes as cdn', 'cdni.credit_debit_note_id', '=', 'cdn.id')
            ->leftJoin('invoice_products as ip', 'cdni.invoice_product_id', '=', 'ip.id')
            ->join('products as p', DB::raw('COALESCE(ip.product_id, cdni.product_id)'), '=', 'p.id')
            ->leftJoin('level3s as l3', 'p.level3_id', '=', 'l3.id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->where('cdn.team_id', $team_id)
            ->where('cdn.season_id', $season_id)
            ->where('cdn.affects_inventory', 1)
            ->whereMonth('cdn.date', $month_id)
            ->select(
                DB::raw('COALESCE(ip.product_id, cdni.product_id) as product_id'),
                'p.name as product_name',
                DB::raw("COALESCE(l3.name, 'Sin clasificar') as level3"),
                DB::raw("COALESCE(l2.name, 'Sin clasificar') as level2"),
                DB::raw("COALESCE(l1.name, 'Sin clasificar') as level1"),
                'cdn.type',
                DB::raw('SUM(cdni.unit_price * cdni.quantity) as total')
            )
            ->groupBy(
                DB::raw('COALESCE(ip.product_id, cdni.product_id)'),
                'p.name', 'l3.name', 'l2.name', 'l1.name', 'cdn.type'
            )
            ->get();

        foreach ($noteRows as $note) {
            $pid = $note->product_id;
            if (!isset($noteAdjustmentsByProduct[$pid])) {
                $noteAdjustmentsByProduct[$pid] = [
                    'product_name' => $note->product_name,
                    'level1' => $note->level1,
                    'level2' => $note->level2,
                    'level3' => $note->level3,
                    'adjustment' => 0.0,
                ];
            }
            $type = strtolower($note->type);
            if ($type === 'credito' || $type === 'nc') {
                $noteAdjustmentsByProduct[$pid]['adjustment'] -= floatval($note->total);
            } else {
                $noteAdjustmentsByProduct[$pid]['adjustment'] += floatval($note->total);
            }
        }

        // -------------------------------------------------------
        // 2. CONSUMIDO del mes: outflows agrupados por fecha de FACTURA/NOTA
        //    Igual que getAllConsumedByMonth: usa invoice->date o cdn->date,
        //    NO la fecha del propio outflow
        // -------------------------------------------------------
        $consumedQuery = DB::table('outflows as o')
            ->leftJoin('invoice_products as ip', 'o.invoice_product_id', '=', 'ip.id')
            ->leftJoin('invoices as i', 'ip.invoice_id', '=', 'i.id')
            ->leftJoin('credit_debit_note_items as cdni', 'o.credit_debit_note_item_id', '=', 'cdni.id')
            ->leftJoin('credit_debit_notes as cdn', 'cdni.credit_debit_note_id', '=', 'cdn.id')
            ->leftJoin('products as p', DB::raw('COALESCE(ip.product_id, cdni.product_id)'), '=', 'p.id')
            // Usar solo la clasificación propia del outflow (o.level3_id)
            ->leftJoin('level3s as l3', 'l3.id', '=', 'o.level3_id')
            ->leftJoin('level2s as l2', 'l3.level2_id', '=', 'l2.id')
            ->leftJoin('level1s as l1', 'l2.level1_id', '=', 'l1.id')
            ->leftJoin('operations as op', 'o.operation_id', '=', 'op.id')
            ->where('o.team_id', $team_id)
            ->where('o.season_id', $season_id)
            ->whereRaw('MONTH(COALESCE(i.date, cdn.date)) = ?', [$month_id])
            ->whereNotNull('p.id');

        // Excluir inversiones si el toggle está desactivado (mismo criterio que el gráfico)
        if (!$includeInvestments) {
            $consumedQuery->where(function($q) {
                $q->whereNull('o.operation_id')
                  ->orWhereRaw('LOWER(op.name) NOT LIKE ?', ['%inversion%']);
            });
        }

        $consumedRows = $consumedQuery
            ->select(
                DB::raw('COALESCE(ip.product_id, cdni.product_id) as product_id'),
                'p.name as product_name',
                DB::raw("COALESCE(l3.name, 'Sin clasificar') as level3"),
                DB::raw("COALESCE(l2.name, 'Sin clasificar') as level2"),
                DB::raw("COALESCE(l1.name, 'Sin clasificar') as level1"),
                DB::raw('SUM(CASE
                    WHEN o.invoice_product_id IS NOT NULL AND ip.id IS NOT NULL THEN o.quantity * ip.unit_price
                    WHEN o.credit_debit_note_item_id IS NOT NULL AND cdni.id IS NOT NULL THEN o.quantity * cdni.unit_price
                    ELSE 0
                END) as total_consumed')
            )
            ->groupBy(DB::raw('COALESCE(ip.product_id, cdni.product_id)'), 'p.name', 'l3.name', 'l2.name', 'l1.name')
            ->get(); // No keyBy: el mismo producto puede tener múltiples filas con distintos level3

        // -------------------------------------------------------
        // 3. Construir filas separadas para consumed e invoiced
        //    Consumed: una fila por (product_id + level3_outflow) — no se colapsan
        //    Invoiced: una fila por product_id — usando level3 del catálogo
        //    El frontend filtra por columna activa (invoiced vs consumed)
        // -------------------------------------------------------

        // Filas de consumed (preservando todas las combinaciones product+level3)
        $consumedResultRows = $consumedRows->map(function ($con) {
            return [
                'level1'         => $con->level1,
                'level2'         => $con->level2,
                'level3'         => $con->level3,
                'product_name'   => $con->product_name,
                'total_invoiced' => 0,
                'total_consumed' => round((float) $con->total_consumed, 0),
            ];
        });

        // Filas de invoiced (una por producto, usando level3 del producto)
        $invoicedResultRows = $invoicedRows->map(function ($inv) use ($noteAdjustmentsByProduct) {
            $note = $noteAdjustmentsByProduct[$inv->product_id] ?? null;
            $totalInvoiced = floatval($inv->total_invoiced) + ($note['adjustment'] ?? 0.0);
            return [
                'level1'         => $inv->level1,
                'level2'         => $inv->level2,
                'level3'         => $inv->level3,
                'product_name'   => $inv->product_name,
                'total_invoiced' => round($totalInvoiced, 0),
                'total_consumed' => 0,
            ];
        })->values();

        // Filas de notas sin factura asociada
        $consumedProductIds = $consumedRows->pluck('product_id')->unique();
        $noteOnlyRows = collect();
        foreach ($noteAdjustmentsByProduct as $productId => $note) {
            if (!$invoicedRows->has($productId) && $note['adjustment'] != 0) {
                $noteOnlyRows->push([
                    'level1'         => $note['level1'],
                    'level2'         => $note['level2'],
                    'level3'         => $note['level3'],
                    'product_name'   => $note['product_name'],
                    'total_invoiced' => round($note['adjustment'], 0),
                    'total_consumed' => 0,
                ]);
            }
        }

        $rows = $consumedResultRows
            ->merge($invoicedResultRows)
            ->merge($noteOnlyRows)
            ->sortBy([
                ['level1', 'asc'],
                ['level2', 'asc'],
                ['level3', 'asc'],
                ['product_name', 'asc'],
            ])->values();

        return response()->json([
            'rows'     => $rows,
            'month_id' => $month_id,
        ]);
    }
}

