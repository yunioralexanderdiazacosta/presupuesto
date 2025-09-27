<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FuelOutflow;
use App\Models\Machinery;
use App\Models\Operator;
use App\Models\CostCenter;

class FuelOutflowController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');
        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $fuelOutflows = FuelOutflow::with(['machinery', 'operator', 'costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20);

        $machineries = Machinery::all(['id', 'cod_machinery']);
        $operators = \App\Models\Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name', 'team_id', 'season_id']);
        $costCenters = CostCenter::all(['id', 'name']);

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
        ]);
    }
    // Aquí puedes agregar métodos agregados, reportes, exportaciones, etc.
}
