<?php

namespace App\Http\Controllers\FertilizerOrders;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOrder;
use App\Models\Product;
use App\Models\CostCenter;
use App\Models\IrrigationPump;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowFertilizerOrderController extends Controller
{
    public function __invoke(FertilizerOrder $fertilizerOrder)
    {
        $user = Auth::user();
        
        // Verificar que la orden pertenezca al equipo del usuario
        if ($fertilizerOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        // Cargar relaciones
        $fertilizerOrder->load([
            'orderProducts.product.unit',
            'orderIrrigationSectors.irrigationSector',
            'orderCostCenters.costCenter',
            'irrigationPump',
            'team',
            'season'
        ]);

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
        $costCenters = CostCenter::where('season_id', session('season_id'))
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
            ->where('season_id', session('season_id'))
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

        $units = Unit::orderBy('name')->get(['id', 'name'])->map(function($unit) {
            return [
                'value' => $unit->id,
                'label' => $unit->name,
            ];
        });

        // Obtener agrupaciones con sus centros de costo
        $season_id = session('season_id');
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
                'name' => $cc->name
            ])->values(),
        ]);

        return Inertia::render('FertilizerOrders/Show', [
            'fertilizerOrder' => $fertilizerOrder,
            'products' => $products,
            'costCenters' => $costCenters,
            'irrigationPumps' => $irrigationPumps,
            'units' => $units,
            'groupings' => $groupings,
        ]);
    }
}
