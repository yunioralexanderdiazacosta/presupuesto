<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\ApplicationOrder;
use App\Models\Product;
use App\Models\CostCenter;
use App\Models\Level3;
use App\Models\Unit;

class ApplicationOrdersController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        // Obtener órdenes de aplicación con relaciones
        $applicationOrders = ApplicationOrder::with([
            'orderProducts.product',
            'orderProducts.unit',
            'orderCostCenters.costCenter',
            'phenologicalStage'
        ])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20);

        // Obtener productos del equipo
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

        // Obtener unidades
        $units = Unit::orderBy('name')->get(['id', 'name'])->map(function($unit) {
            return [
                'value' => $unit->id,
                'label' => $unit->name,
            ];
        });

        // Obtener agrupaciones con sus centros de costo
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

        return Inertia::render('ApplicationOrders/Index', [
            'applicationOrders' => $applicationOrders,
            'products' => $products,
            'costCenters' => $costCenters,
            'units' => $units,
            'groupings' => $groupings,
            'fruits' => $fruits,
            'phenologicalStages' => $phenologicalStages,
        ]);
    }
}
