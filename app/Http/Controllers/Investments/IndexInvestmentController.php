<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\CostCenter;
use App\Models\Season;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class IndexInvestmentController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $season_id = session('season_id');
        $investments = Investment::with(['costCenters', 'responsable', 'season'])
            ->where('season_id', $season_id)
            ->latest()
            ->paginate(20);
        $costCenters = CostCenter::all(['id', 'name']);
        $seasons = Season::where('team_id', $user->team_id)->get(['id', 'name']);
        $users = User::all(['id', 'name']);
        return Inertia::render('Investments/Index', [
            'investments' => $investments,
            'costCenters' => $costCenters,
            'seasons' => $seasons,
            'users' => $users,
        ]);
    }
}
