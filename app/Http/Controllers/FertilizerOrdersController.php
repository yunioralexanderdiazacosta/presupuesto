<?php

namespace App\Http\Controllers;

use App\Models\FertilizerOrder;
use App\Models\Product;
use App\Models\CostCenter;
use App\Models\IrrigationPump;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FertilizerOrdersController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $fertilizerOrders = FertilizerOrder::with([
            'orderProducts.product',
            'orderProducts.unit',
            'orderIrrigationSectors.irrigationSector',
            'orderCostCenters.costCenter',
            'irrigationPump'
        ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->get();

        // Obtener productos de fertilizantes del equipo
        $products = Product::with('unit:id,name')
            ->whereHas('level2', function($query) {
                $query->where('name', 'fertilizantes');
            })
            ->where('team_id', $user->team_id)
            ->get(['id', 'name', 'unit_id', 'level2_id'])
            ->map(function($product) {
                return [
                    'value' => $product->id,
                    'label' => $product->name,
                    'unit_id' => $product->unit_id,
                    'unit_name' => $product->unit->name ?? '',
                ];
            });

        // Obtener centros de costo
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name', 'surface'])
            ->map(function($cc) {
                return [
                    'value' => $cc->id,
                    'label' => $cc->name,
                    'surface' => $cc->surface ?? 0,
                ];
            });

        // Obtener bombas de riego
        $irrigationPumps = IrrigationPump::with('sectors')
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get()
            ->map(function($pump) {
                return [
                    'value' => $pump->id,
                    'label' => $pump->name,
                    'sectors' => $pump->sectors->map(function($sector) {
                        return [
                            'value' => $sector->id,
                            'label' => $sector->name,
                            'surface' => $sector->surface,
                        ];
                    }),
                ];
            });

        // Obtener unidades
        $units = Unit::orderBy('name')->get(['id', 'name'])->map(function($unit) {
            return [
                'value' => $unit->id,
                'label' => $unit->name,
            ];
        });

        return Inertia::render('FertilizerOrders/Index', [
            'fertilizerOrders' => $fertilizerOrders,
            'products' => $products,
            'costCenters' => $costCenters,
            'irrigationPumps' => $irrigationPumps,
            'units' => $units,
        ]);
    }
}
