<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use Illuminate\Support\Facades\Auth;

class EstimatesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        $costcenters = \App\Models\CostCenter::where('season_id', $season_id)
            ->with('variety')
            ->get();

        $estimates = Estimate::where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->with('estimateStatus')
            ->get();

        // Frutas relacionadas al team (por costcenters del team)
        // Frutas relacionadas al team directamente por campo team_id en fruits
        $fruits = \App\Models\Fruit::where('team_id', $user->team_id)->get();

        // Estados relacionados a la fruta seleccionada
        $estimate_statuses = \App\Models\EstimateStatus::whereIn('fruit_id', $fruits->pluck('id'))->get();

        return \Inertia\Inertia::render('Estimates', [
            'costcenters' => $costcenters,
            'estimates' => $estimates,
            'estimate_statuses' => $estimate_statuses,
            'fruits' => $fruits,
        ]);
    }
}
