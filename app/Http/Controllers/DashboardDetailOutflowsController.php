<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\HasInventory;
use App\Traits\HasOutflowBranchStats;
use App\Traits\HasOutflowHectareStats;

class DashboardDetailOutflowsController extends Controller
{
    use HasInventory, HasOutflowBranchStats, HasOutflowHectareStats;

    public function index(Request $request)
    {
        $season_id = session('season_id');
        $user = Auth::user();
        $team_id = $user->team_id;

        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        $branches = Branch::where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name])
            ->values();

        return Inertia::render('DashboardDetailOutflows', [
            'consumoPorSucursal'      => $this->getConsumoPorSucursal($team_id, $season_id),
            'stockValorizado'         => $this->getValorizedInventory($team_id, $season_id),
            'branches'                => $branches,
            'consumoPorHectarea'      => $this->getConsumoPorHectarea($team_id, $season_id),
            'superficiePorSucursal'   => $this->getSuperficiePorSucursalEstado($team_id, $season_id),
            'developmentStates'       => $this->getDevelopmentStatesForSeason($season_id),
        ]);
    }
}
