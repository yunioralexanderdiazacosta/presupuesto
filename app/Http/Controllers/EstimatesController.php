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
            ->get();

        return \Inertia\Inertia::render('Estimates', [
            'costcenters' => $costcenters,
            'estimates' => $estimates,
        ]);
    }
}
