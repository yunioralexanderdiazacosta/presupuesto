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
        
        $branches = \App\Models\Branch::pluck('name', 'id');

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
                    'branch_id' => $invoiceProduct->branch_id,
                    'branch_name' => $invoiceProduct->branch_id ? ($branches[$invoiceProduct->branch_id] ?? null) : null,
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
                    'branch_id' => null,
                    'branch_name' => null,
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

        // Sucursales únicas presentes en los stocks de combustible (para el select de filtro)
        $branchesForSelect = collect($availableFuelStocks)
            ->filter(fn($s) => $s['branch_id'] !== null)
            ->unique('branch_id')
            ->map(fn($s) => ['value' => $s['branch_id'], 'label' => $s['branch_name']])
            ->values();

        // Agrupaciones con sus centros de costo
        $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($season_id) {
            $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $season_id);
        }])
        ->where('season_id', $season_id)
        ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
        ->get()
        ->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'cost_centers' => $g->costCenters->map(fn($cc) => [
                'id' => $cc->id,
                'name' => $cc->name,
            ])->values(),
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
            'branches' => $branchesForSelect,
            'groupings' => $groupings,
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
            ->leftJoin('branches', 'machineries.branch_id', '=', 'branches.id')
            ->where('fuel_outflows.team_id', $user->team_id)
            ->where('fuel_outflows.season_id', $season_id)
            ->select(
                'machineries.id as machinery_id',
                'machineries.cod_machinery as machinery_name',
                'machineries.branch_id',
                'branches.name as branch_name',
                DB::raw('SUM(fuel_outflows.liters) as total_litros'),
                DB::raw('COUNT(*) as cantidad_registros'),
                DB::raw('AVG(fuel_outflows.liters) as promedio_litros')
            )
            ->groupBy('machineries.id', 'machineries.cod_machinery', 'machineries.branch_id', 'branches.name')
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

        // Consumption averages per machinery (L/hour or L/km)
        // Logic: liters loaded at reading N are consumed between reading N and N+1.
        // Therefore the last record's liters must be excluded (no next reading yet).
        $consumptionAverages = DB::table('fuel_outflows')
            ->join('machineries', 'fuel_outflows.machinery_id', '=', 'machineries.id')
            ->leftJoin('counters', 'fuel_outflows.counter_id', '=', 'counters.id')
            ->leftJoin('branches', 'machineries.branch_id', '=', 'branches.id')
            ->where('fuel_outflows.team_id', $user->team_id)
            ->where('fuel_outflows.season_id', $season_id)
            ->whereNotNull('fuel_outflows.counter_value')
            ->select(
                'machineries.id as machinery_id',
                'machineries.cod_machinery as machinery_name',
                'machineries.branch_id',
                'branches.name as branch_name',
                'counters.id as counter_id',
                'counters.name as counter_name',
                DB::raw('SUM(fuel_outflows.liters) as total_liters'),
                DB::raw('COUNT(*) as record_count'),
                DB::raw('MIN(fuel_outflows.counter_value) as min_counter'),
                DB::raw('MAX(fuel_outflows.counter_value) as max_counter')
            )
            ->groupBy('machineries.id', 'machineries.cod_machinery', 'machineries.branch_id', 'branches.name', 'counters.id', 'counters.name')
            ->orderByDesc('total_liters')
            ->get();

        // Get liters from the LAST counter reading per machinery (to exclude — not yet consumed)
        $lastReadingLiters = DB::table('fuel_outflows as a')
            ->join(
                DB::raw('(SELECT machinery_id, MAX(counter_value) as max_cv FROM fuel_outflows WHERE team_id = ' . (int)$user->team_id . ' AND season_id = ' . (int)$season_id . ' AND counter_value IS NOT NULL GROUP BY machinery_id) as b'),
                function ($join) {
                    $join->on('a.machinery_id', '=', 'b.machinery_id')
                         ->on('a.counter_value', '=', 'b.max_cv');
                }
            )
            ->where('a.team_id', $user->team_id)
            ->where('a.season_id', $season_id)
            ->select('a.machinery_id', 'a.liters as last_liters')
            ->pluck('last_liters', 'machinery_id');

        $consumptionAverages = $consumptionAverages->map(function ($row) use ($lastReadingLiters) {
            $totalLiters     = (float) $row->total_liters;
            $lastLiters      = (float) ($lastReadingLiters[$row->machinery_id] ?? 0);
            $effectiveLiters = max(0, $totalLiters - $lastLiters);
            $counterDelta    = (float) $row->max_counter - (float) $row->min_counter;
            $unit            = $row->counter_name ?? '—';
            $isKm            = str_contains(strtolower($unit), 'odom') || str_contains(strtolower($unit), 'km');
            $unitLabel       = $isKm ? 'km/L' : 'L/h';

            return [
                'machinery_id'     => $row->machinery_id,
                'machinery_name'   => $row->machinery_name,
                'branch_id'        => $row->branch_id,
                'branch_name'      => $row->branch_name,
                'counter_name'     => $unit,
                'unit_label'       => $unitLabel,
                'is_odometer'      => $isKm,
                'total_liters'     => round($totalLiters, 2),
                'effective_liters' => round($effectiveLiters, 2),
                'last_liters'      => round($lastLiters, 2),
                'record_count'     => (int) $row->record_count,
                'counter_delta'    => round($counterDelta, 1),
                'min_counter'      => round((float) $row->min_counter, 1),
                'max_counter'      => round((float) $row->max_counter, 1),
                // Para odómetro: km/L (mayor es mejor). Para horómetro: L/h.
                'avg_per_unit'     => ($counterDelta > 0 && $effectiveLiters > 0 && (int)$row->record_count >= 2)
                                        ? ($isKm
                                            ? round($counterDelta / $effectiveLiters, 3)   // km/L
                                            : round($effectiveLiters / $counterDelta, 3))  // L/h
                                        : null,
            ];
        });

        return response()->json([
            'consumo_por_maquinaria'  => $consumoPorMaquinaria,
            'stock_por_estanque'      => $stockPorEstanque,
            'consumption_averages'    => $consumptionAverages,
        ]);
    }
}
