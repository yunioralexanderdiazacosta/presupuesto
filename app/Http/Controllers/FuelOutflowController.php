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


    $fuelOutflows = FuelOutflow::with(['machinery', 'operator', 'costCenters.costCenter'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->latest('date')
            ->paginate(20)
            ->through(function ($item) {
                $item->costCenters = $item->costCenters->map(function($cc) {
                    return [
                        'name' => $cc->costCenter->name ?? '',
                        'observations' => $cc->observations ?? null,
                    ];
                });
                return $item;
            });

        $machineries = Machinery::all(['id', 'cod_machinery']);
        $operators = \App\Models\Operator::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->get(['id', 'name', 'team_id', 'season_id']);
        $costCenters = CostCenter::all(['id', 'name']);
        // ...existing code...

        return Inertia::render('FuelOutflows/Index', [
            'fuelOutflows' => $fuelOutflows,
            'machineries' => $machineries,
            'operators' => $operators,
            'costCenters' => $costCenters,
            // ...existing code...
        ]);
    }
    // Aquí puedes agregar métodos agregados, reportes, exportaciones, etc.
}
