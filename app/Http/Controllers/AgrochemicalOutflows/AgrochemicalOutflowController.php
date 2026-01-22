<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AgrochemicalOutflowController
{
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $outflows = AgrochemicalOutflow::with([
            'applicationOrder',
            'product.unit',
            'costCenter',
            'invoiceProduct.invoice',
            'team',
            'season'
        ])
        ->where('team_id', $teamId)
        ->where('season_id', $seasonId)
        ->orderBy('date', 'desc')
        ->orderBy('created_at', 'desc')
        ->paginate(50);

        // Obtener órdenes disponibles para ejecutar con sus productos
        $availableOrders = ApplicationOrder::with([
            'orderProducts.product.unit', 
            'orderCostCenters.costCenter'
        ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', '!=', 'cancelada')
            ->orderBy('date', 'desc')
            ->get();

        // Calcular stock disponible de agroquímicos por producto
        $availableStocksByProduct = $this->calculateAvailableStocks($teamId, $seasonId);

        return Inertia::render('AgrochemicalOutflows/Index', [
            'outflows' => $outflows,
            'availableOrders' => $availableOrders,
            'availableStocksByProduct' => $availableStocksByProduct,
        ]);
    }

    private function calculateAvailableStocks($teamId, $seasonId)
    {
        // Calcular consumos por invoice_product_id desde agrochemical_outflows
        $consumosByInvoiceProduct = DB::table('agrochemical_outflows')
            ->select('invoice_product_id', DB::raw('SUM(quantity) as total_consumido'))
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->whereNotNull('invoice_product_id')
            ->groupBy('invoice_product_id')
            ->pluck('total_consumido', 'invoice_product_id');

        // Devoluciones (notas de crédito)
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $teamId)
            ->where('credit_debit_notes.season_id', $seasonId)
            ->where('credit_debit_notes.type', 'credito')
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');

        // Traer facturas con productos
        $stocksByProduct = [];

        $invoices = Invoice::with(['supplier', 'typeDocument', 'products.unit'])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->get();

        foreach ($invoices as $invoice) {
            foreach ($invoice->products as $product) {
                // Excluir si tiene nota de crédito
                $hasCreditNote = DB::table('credit_debit_note_items')
                    ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                    ->where('credit_debit_notes.type', 'credito')
                    ->where('credit_debit_note_items.invoice_product_id', $product->pivot->id)
                    ->exists();

                if ($hasCreditNote) {
                    continue;
                }

                $consumido = $consumosByInvoiceProduct[$product->pivot->id] ?? 0;
                $devuelto = $creditNotesReturns[$product->pivot->id] ?? 0;
                $cantidadOriginal = $product->pivot->amount ?? 0;
                $stockDisponible = $cantidadOriginal - $consumido - $devuelto;

                if ($stockDisponible <= 0) {
                    continue;
                }

                // Agrupar por product_id
                if (!isset($stocksByProduct[$product->id])) {
                    $stocksByProduct[$product->id] = [];
                }

                $stocksByProduct[$product->id][] = [
                    'invoice_product_id' => $product->pivot->id,
                    'number_document' => $invoice->number_document,
                    'supplier' => $invoice->supplier->name ?? '-',
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit' => $product->unit->name ?? '-',
                    'unit_price' => $product->pivot->unit_price ?? 0,
                    'date' => $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('Y-m-d') : $invoice->date,
                ];
            }
        }

        return $stocksByProduct;
    }
}
