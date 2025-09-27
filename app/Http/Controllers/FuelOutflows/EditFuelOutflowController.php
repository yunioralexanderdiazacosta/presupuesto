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

        return Inertia::render('FuelOutflows/Edit', [
            'fuelOutflow' => $fuelOutflow->load(['machinery', 'operator', 'costCenter']),
            'machineries' => Machinery::all(),
            'operators' => $operators,
            'costCenters' => CostCenter::all(),
        ]);
    }
}
