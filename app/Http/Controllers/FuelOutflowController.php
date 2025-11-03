<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\Counter;
use App\Models\Invoice;
use App\Models\Level3;
// ...existing code...

class FuelOutflowController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }


    $fuelOutflows = FuelOutflow::with(['machinery.counter', 'operator', 'product', 'counter', 'costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20);
            
        // Transformar la colección dentro del paginador
        $fuelOutflows->getCollection()->transform(function ($item) {
            $item->costCenters = $item->costCenters->map(function($cc) {
                return [
                    'cost_center_id' => $cc->cost_center_id,
                    'name' => $cc->costCenter->name ?? '',
                    'observations' => $cc->observations ?? null,
                ];
            });
            return $item;
        });

        $machineries = Machinery::with('counter:id,name')
            ->where('team_id', $user->team_id)
            ->get(['id', 'cod_machinery', 'counter_id'])
            ->map(function($machinery) {
                return [
                    'value' => $machinery->id,
                    'label' => $machinery->cod_machinery,
                    'counter_id' => $machinery->counter_id,
                    'counter_name' => $machinery->counter ? $machinery->counter->name : null,
                ];
            });
        $operators = \App\Models\Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name', 'team_id', 'season_id']);
        $costCenters = CostCenter::all(['id', 'name']);
        
        // ========================================
        // 🔥 NUEVO: Calcular stock disponible de COMBUSTIBLES
        // ========================================
        
        // 1. Obtener IDs de productos clasificados como "Combustible" (level3)
        $combustibleLevel3 = Level3::where('name', 'combustible')->first();
        $combustibleProductIds = collect();
        
        if ($combustibleLevel3) {
            $combustibleProductIds = Product::where('level3_id', $combustibleLevel3->id)
                ->where('team_id', $user->team_id)
                ->pluck('id');
        }
        
        // 2. Calcular salidas de combustible (FuelOutflows)
        $fuelOutflowsByInvoiceProduct = DB::table('fuel_outflows')
            ->select('invoice_product_id', DB::raw('SUM(liters) as total_consumido'))
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('invoice_product_id')
            ->groupBy('invoice_product_id')
            ->pluck('total_consumido', 'invoice_product_id');
        
        $fuelOutflowsByDebitNote = DB::table('fuel_outflows')
            ->select('credit_debit_note_item_id', DB::raw('SUM(liters) as total_consumido'))
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->whereNotNull('credit_debit_note_item_id')
            ->groupBy('credit_debit_note_item_id')
            ->pluck('total_consumido', 'credit_debit_note_item_id');
        
        // 3. Precalcular devoluciones (notas de crédito)
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');
        
        // 4. Traer líneas de facturas de combustibles
        $availableFuelStocks = [];
        
        $invoices = Invoice::with(['supplier', 'typeDocument', 'invoiceProducts.product.unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get();
        
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                // Filtrar solo productos de combustible
                if ($combustibleProductIds->isEmpty() || !$combustibleProductIds->contains($invoiceProduct->product_id)) {
                    continue;
                }
                
                // Excluir si tiene nota de crédito
                $hasCreditNote = DB::table('credit_debit_note_items')
                    ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                    ->where('credit_debit_notes.type', 'credito')
                    ->where('credit_debit_note_items.invoice_product_id', $invoiceProduct->id)
                    ->exists();
                
                if ($hasCreditNote) {
                    continue;
                }
                
                $consumido = $fuelOutflowsByInvoiceProduct[$invoiceProduct->id] ?? 0;
                $devuelto = $creditNotesReturns[$invoiceProduct->id] ?? 0;
                $cantidadOriginal = $invoiceProduct->quantity ?? $invoiceProduct->amount ?? 0;
                $stockDisponible = $cantidadOriginal - $consumido - $devuelto;
                
                if ($stockDisponible <= 0) {
                    continue; // No mostrar si no hay stock
                }
                
                $availableFuelStocks[] = [
                    'origen' => $invoice->typeDocument?->name ?? 'factura',
                    'invoice_product_id' => $invoiceProduct->id,
                    'credit_debit_note_item_id' => null,
                    'number_document' => $invoice->number_document,
                    'supplier' => $invoice->supplier->name ?? '-',
                    'product_id' => $invoiceProduct->product_id,
                    'product_name' => $invoiceProduct->product->name ?? '-',
                    'unit' => $invoiceProduct->product->unit->name ?? '-',
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit_price' => $invoiceProduct->unit_price ?? 0,
                    'date' => $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('Y-m-d') : $invoice->date,
                ];
            }
        }
        
        // 5. Traer notas de débito de combustibles
        $debitNotes = \App\Models\CreditDebitNote::with(['supplier', 'items.product.unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->where('type', 'debito')
            ->get();
        
        foreach ($debitNotes as $note) {
            foreach ($note->items as $item) {
                // Filtrar solo productos de combustible
                if ($combustibleProductIds->isEmpty() || !$combustibleProductIds->contains($item->product_id)) {
                    continue;
                }
                
                $consumido = $fuelOutflowsByDebitNote[$item->id] ?? 0;
                $cantidadOriginal = $item->quantity ?? 0;
                $stockDisponible = $cantidadOriginal - $consumido;
                
                if ($stockDisponible <= 0) {
                    continue;
                }
                
                $availableFuelStocks[] = [
                    'origen' => 'nota_debito',
                    'invoice_product_id' => null,
                    'credit_debit_note_item_id' => $item->id,
                    'number_document' => $note->number,
                    'supplier' => $note->supplier->name ?? '-',
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? '-',
                    'unit' => $item->unit->name ?? '-',
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit_price' => 0,
                    'date' => $note->date instanceof \Carbon\Carbon ? $note->date->format('Y-m-d') : $note->date,
                ];
            }
        }
        
        // ========================================
        // 🔥 FIN: Sistema de stock disponible
        // ========================================
        
        // Obtener productos de combustible (level3 = 'combustible')
        $fuelProducts = Product::whereHas('level3', function($query) {
            $query->where('name', 'combustible');
        })
        ->where('team_id', $user->team_id)
        ->get(['id', 'name'])
        ->map(function($product) {
            return [
                'value' => $product->id,
                'label' => $product->name
            ];
        });

        // Obtener todos los counters
        $counters = Counter::all(['id', 'name'])->map(function($counter) {
            return [
                'value' => $counter->id,
                'label' => $counter->name
            ];
        });

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'availableFuelStocks' => $availableFuelStocks,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
            'fuelProducts' => $fuelProducts,
            'counters' => $counters,
        ]);
    }
    // Aquí puedes agregar métodos agregados, reportes, exportaciones, etc.
}
