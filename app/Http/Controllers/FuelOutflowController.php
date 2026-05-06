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
use App\Models\FuelTank;

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


    $fuelOutflows = FuelOutflow::with(['machinery.counter', 'operator', 'product', 'counter', 'outflow.costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->get();
            
        // Transformar la colección
        $fuelOutflows->transform(function ($item) {
            // Obtener centros de costo desde el outflow relacionado
            $item->costCenters = $item->outflow && $item->outflow->costCenters 
                ? $item->outflow->costCenters->map(function($cc) {
                    return [
                        'cost_center_id' => $cc->cost_center_id,
                        'name' => $cc->costCenter->name ?? '',
                        'observations' => $cc->observations ?? null,
                    ];
                })
                : collect([]);
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
        
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name']);
        
        // ========================================
        // 🔥 NUEVO: Calcular stock disponible de COMBUSTIBLES
        // ========================================
        
        // 1. Obtener IDs de level3 "combustible" que pertenecen al team del usuario
        // level3 -> level2 -> level1 -> team_id
        $combustibleLevel3Ids = Level3::where('name', 'combustible')
            ->whereHas('level2.level1', function($query) use ($user) {
                $query->where('team_id', $user->team_id);
            })
            ->pluck('id');
        
        // Obtener IDs de productos de combustible del team
        $combustibleProductIds = Product::whereIn('level3_id', $combustibleLevel3Ids)
            ->where('team_id', $user->team_id)
            ->pluck('id');
        
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
        
        // 3. Precalcular devoluciones (notas de crédito que afectan inventario)
        $creditNotesReturns = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 1)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity) as total_devuelto'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('total_devuelto', 'credit_debit_note_items.invoice_product_id');
        
        // Precalcular NC financieras (affects_inventory=0) por invoice_product
        $financialNCsByIP = DB::table('credit_debit_note_items')
            ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
            ->where('credit_debit_notes.team_id', $user->team_id)
            ->where('credit_debit_notes.season_id', $season_id)
            ->where('credit_debit_notes.type', 'credito')
            ->where('credit_debit_notes.affects_inventory', 0)
            ->whereNotNull('credit_debit_note_items.invoice_product_id')
            ->select('credit_debit_note_items.invoice_product_id', DB::raw('SUM(credit_debit_note_items.quantity * credit_debit_note_items.unit_price) as nc_total'))
            ->groupBy('credit_debit_note_items.invoice_product_id')
            ->pluck('nc_total', 'credit_debit_note_items.invoice_product_id');

        // 4. Traer líneas de facturas de combustibles
        $availableFuelStocks = [];
        
        $invoices = Invoice::with(['supplier', 'typeDocument', 'invoiceProducts.product.unit', 'invoiceProducts.product.level3'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get();
        
        foreach ($invoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                $product = $invoiceProduct->product;
                
                if (!$product) {
                    continue;
                }

                // Filtrar solo productos de combustible
                if ($combustibleProductIds->isEmpty() || !$combustibleProductIds->contains($product->id)) {
                    continue;
                }
                
                // Excluir solo si tiene nota de crédito de anulación que afecta inventario
                $hasAnnulmentNote = DB::table('credit_debit_note_items')
                    ->join('credit_debit_notes', 'credit_debit_note_items.credit_debit_note_id', '=', 'credit_debit_notes.id')
                    ->where('credit_debit_notes.type', 'credito')
                    ->where('credit_debit_notes.is_annulment', 1)
                    ->where('credit_debit_notes.affects_inventory', 1)
                    ->where('credit_debit_note_items.invoice_product_id', $invoiceProduct->id)
                    ->exists();
                
                if ($hasAnnulmentNote) {
                    continue;
                }
                
                $consumido = $fuelOutflowsByInvoiceProduct[$invoiceProduct->id] ?? 0;
                $devuelto = $creditNotesReturns[$invoiceProduct->id] ?? 0;
                $cantidadOriginal = $invoiceProduct->amount ?? 0;
                $stockDisponible = round($cantidadOriginal - $consumido - $devuelto, 2);
                
                if ($stockDisponible <= 0) {
                    continue;
                }
                
                $unitPrice = $invoiceProduct->unit_price ?? 0;
                $ncFinanciero = $financialNCsByIP[$invoiceProduct->id] ?? 0;
                $effectiveUnitPrice = $cantidadOriginal > 0
                    ? round($unitPrice - ($ncFinanciero / $cantidadOriginal), 2)
                    : $unitPrice;

                $availableFuelStocks[] = [
                    'origen' => $invoice->typeDocument?->name ?? 'factura',
                    'invoice_product_id' => $invoiceProduct->id,
                    'credit_debit_note_item_id' => null,
                    'number_document' => $invoice->number_document,
                    'supplier' => $invoice->supplier->name ?? '-',
                    'product_id' => $product->id,
                    'product_name' => $product->name ?? '-',
                    'unit' => $product->unit->name ?? '-',
                    'cantidad_original' => $cantidadOriginal,
                    'stock_disponible' => $stockDisponible,
                    'unit_price' => $unitPrice,
                    'effective_unit_price' => $effectiveUnitPrice,
                    'tank_id' => $invoiceProduct->tank_id,
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
                $stockDisponible = round($cantidadOriginal - $consumido, 2);
                
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
                    'tank_id' => null,
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

        // Obtener proyectos y operaciones para los selects
        $projects = \App\Models\Project::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name'])
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name])
            ->values();
        
        $operations = \App\Models\Operation::all(['id', 'name'])
            ->map(fn($o) => ['value' => $o->id, 'label' => $o->name])
            ->values();

        // Estanques activos del equipo
        $fuelTanks = FuelTank::with(['branch', 'product'])
            ->where('team_id', $user->team_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'value'        => $t->id,
                'label'        => $t->name,
                'branch_id'    => $t->branch_id,
                'branch_name'  => $t->branch?->name,
                'product_id'   => $t->product_id,
                'product_name' => $t->product?->name,
            ]);

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'availableFuelStocks' => $availableFuelStocks,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
            'fuelProducts' => $fuelProducts,
            'counters' => $counters,
            'projects' => $projects,
            'operations' => $operations,
            'fuelTanks' => $fuelTanks,
        ]);
    }
    
    /**
     * Obtener datos de análisis de consumo de combustible
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        
        // Consumo total por maquinaria
        $consumoPorMaquinaria = DB::table('fuel_outflows')
            ->join('machineries', 'fuel_outflows.machinery_id', '=', 'machineries.id')
            ->where('fuel_outflows.team_id', $user->team_id)
            ->where('fuel_outflows.season_id', $season_id)
            ->select(
                'machineries.id as machinery_id',
                'machineries.cod_machinery as machinery_name',
                DB::raw('SUM(fuel_outflows.liters) as total_litros'),
                DB::raw('COUNT(*) as cantidad_registros'),
                DB::raw('AVG(fuel_outflows.liters) as promedio_litros')
            )
            ->groupBy('machineries.id', 'machineries.cod_machinery')
            ->orderByDesc('total_litros')
            ->get();

        // Stock por estanque
        $tanks = FuelTank::with(['branch', 'product'])
            ->where('team_id', $user->team_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $stockPorEstanque = $tanks->map(function ($tank) use ($season_id) {
            // Litros ingresados al estanque (de facturas)
            $ingresado = DB::table('invoice_products')
                ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
                ->where('invoice_products.tank_id', $tank->id)
                ->where('invoices.season_id', $season_id)
                ->sum('invoice_products.amount');

            // Litros consumidos desde el estanque
            $consumido = DB::table('fuel_outflows')
                ->where('tank_id', $tank->id)
                ->where('season_id', $season_id)
                ->sum('liters');

            $stock = round($ingresado - $consumido, 2);

            return [
                'tank_id'      => $tank->id,
                'tank_name'    => $tank->name,
                'branch_name'  => $tank->branch?->name,
                'product_name' => $tank->product?->name,
                'capacity'     => $tank->capacity,
                'ingresado'    => round($ingresado, 2),
                'consumido'    => round($consumido, 2),
                'stock'        => $stock,
                'porcentaje'   => $tank->capacity > 0 ? round(($stock / $tank->capacity) * 100, 1) : null,
            ];
        });

        return response()->json([
            'consumo_por_maquinaria' => $consumoPorMaquinaria,
            'stock_por_estanque'     => $stockPorEstanque,
        ]);
    }
}
