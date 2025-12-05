<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\Counter;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditFuelOutflowController
{
    public function __invoke(FuelOutflow $fuelOutflow)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $operators = Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name']);
        
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name']);

        // Cargar el fuelOutflow con su outflow y los costCenters del outflow
        $fuelOutflow->load(['machinery', 'operator', 'outflow.costCenters.costCenter', 'outflow.project', 'outflow.operation']);
        
        // Transformar costCenters desde el outflow al formato esperado
        $fuelOutflow->costCenters = $fuelOutflow->outflow && $fuelOutflow->outflow->costCenters 
            ? $fuelOutflow->outflow->costCenters->map(function($cc) {
                return [
                    'cost_center_id' => $cc->cost_center_id,
                    'name' => $cc->costCenter->name ?? '',
                    'observations' => $cc->observations ?? null,
                ];
            })
            : collect([]);

        // Obtener catálogos para los selects
        $machineries = Machinery::where('team_id', $user->team_id)
            ->get(['id', 'cod_machinery', 'brand', 'counter_id'])
            ->map(function($machinery) {
                return [
                    'value' => $machinery->id,
                    'label' => $machinery->cod_machinery,
                    'counter_id' => $machinery->counter_id,
                    'counter_name' => $machinery->counter ? $machinery->counter->name : null,
                ];
            });

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

        $counters = Counter::all(['id', 'name'])->map(function($counter) {
            return [
                'value' => $counter->id,
                'label' => $counter->name
            ];
        });

        $projects = \App\Models\Project::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name'])
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->name])
            ->values();
        
        $operations = \App\Models\Operation::all(['id', 'name'])
            ->map(fn($o) => ['value' => $o->id, 'label' => $o->name])
            ->values();

        return Inertia::render('FuelOutflows/Edit', [
            'fuelOutflow' => $fuelOutflow,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
            'fuelProducts' => $fuelProducts,
            'counters' => $counters,
            'projects' => $projects,
            'operations' => $operations,
        ]);
    }
}
