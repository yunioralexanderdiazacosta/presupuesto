<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Http\Controllers\Controller;
use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class IndexFuelOutflowController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $fuelOutflows = FuelOutflow::with(['machinery', 'operator', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20);


        $machineries = Machinery::all(['id', 'cod_machinery']);
        $operators = Operator::all(['id', 'name']);
        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name']);

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
        ]);
    }
}
