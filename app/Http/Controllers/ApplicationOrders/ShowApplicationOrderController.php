<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use App\Models\Product;
use App\Models\CostCenter;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowApplicationOrderController
{
    public function __invoke(ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        
        // Validar que la orden pertenezca al equipo del usuario
        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        // Cargar todas las relaciones necesarias
        $applicationOrder->load([
            'orderProducts.product.unit',
            'orderProducts.unit',
            'orderCostCenters.costCenter',
            'phenologicalStage',
            'team',
            'season'
        ]);
        
        // Obtener productos del equipo para el modal de edición
        $products = Product::with('unit:id,name')
            ->where('team_id', $user->team_id)
            ->get(['id', 'name', 'unit_id'])
            ->map(function($product) {
                return [
                    'value' => $product->id,
                    'label' => $product->name,
                    'unit_id' => $product->unit_id,
                    'unit_name' => $product->unit->name ?? '',
                ];
            });

        // Obtener centros de costo para el modal de edición
        $costCenters = CostCenter::where('season_id', $seasonId)
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

        // Obtener unidades
        $units = Unit::orderBy('name')->get(['id', 'name'])->map(function($unit) {
            return [
                'value' => $unit->id,
                'label' => $unit->name,
            ];
        });

        // Obtener agrupaciones con sus centros de costo
        $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($seasonId) {
            $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $seasonId);
        }])
        ->where('season_id', $seasonId)
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

        // Obtener frutales del equipo
        $fruits = \App\Models\Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function($fruit) {
                return [
                    'value' => $fruit->id,
                    'label' => $fruit->name,
                ];
            });

        // Obtener etapas fenológicas del equipo con su frutal
        $phenologicalStages = \App\Models\PhenologicalStage::with('fruit:id,name')
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'fruit_id'])
            ->map(function($stage) {
                return [
                    'value' => $stage->id,
                    'label' => $stage->name,
                    'fruit_id' => $stage->fruit_id,
                ];
            });
        
        return Inertia::render('ApplicationOrders/Show', [
            'applicationOrder' => $applicationOrder,
            'products' => $products,
            'costCenters' => $costCenters,
            'units' => $units,
            'groupings' => $groupings,
            'fruits' => $fruits,
            'phenologicalStages' => $phenologicalStages,
        ]);
    }
}
