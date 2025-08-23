<?php

namespace App\Http\Controllers\Outflows;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\InvoiceProduct;
use App\Models\Project;
use App\Models\Operation;
use App\Models\Machinery;
use App\Models\Team;
use App\Models\Season;
use App\Models\CostCenter;
use Inertia\Inertia;

class CreateOutflowController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Inertia::render('Outflows/Create', [
            'invoiceProducts' => InvoiceProduct::with('invoice', 'product')
                ->whereHas('invoice', fn($q) => $q->where('team_id', $user->team_id)->where('season_id', session('season_id')))
                ->get(),
            'projects' => Project::where('team_id', $user->team_id)->get(),
            'operations' => Operation::all(),
            'machineries' => Machinery::all(),
            'teams' => Team::where('id', $user->team_id)->get(),
            'seasons' => Season::where('id', session('season_id'))->get(),
            'costCenters' => CostCenter::where('team_id', $user->team_id)->get(),
        ]);
    }
}
