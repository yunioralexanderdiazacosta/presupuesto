<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;
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
        $fuelOutflow->load(['machinery', 'operator', 'outflow.costCenters.costCenter']);
        
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

        return Inertia::render('FuelOutflows/Edit', [
            'fuelOutflow' => $fuelOutflow,
            'machineries' => Machinery::all(),
            'operators' => $operators,
            'costCenters' => $costCenters,
        ]);
    }
}
