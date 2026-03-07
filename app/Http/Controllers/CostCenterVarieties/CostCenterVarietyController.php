<?php

namespace App\Http\Controllers\CostCenterVarieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CostCenter;
use App\Models\DevelopmentState;
use App\Models\Fruit;
use App\Models\Rootstock;
use App\Models\Variety;
use Inertia\Inertia;

class CostCenterVarietyController extends Controller
{
    public function __invoke(Request $request)
    {
        $user      = Auth::user();
        $season_id = session('season_id');

        $costCenters = CostCenter::where('season_id', $season_id)
            ->whereHas('season', fn($q) => $q->where('team_id', $user->team_id))
            ->orderBy('name')
            ->get(['id', 'name', 'surface']);

        $costCentersData = $costCenters->map(fn($c) => ['id' => $c->id, 'surface' => (float) $c->surface]);
        $costCenters     = $costCenters->map(fn($c) => ['label' => $c->name, 'value' => $c->id]);

        $fruits = Fruit::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($f) => ['label' => $f->name, 'value' => $f->id]);

        $varieties = Variety::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'fruit_id'])
            ->map(fn($v) => ['label' => $v->name, 'value' => $v->id, 'fruit_id' => $v->fruit_id]);

        $rootstocks = Rootstock::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($r) => ['label' => $r->name, 'value' => $r->id]);

        $developmentStates = DevelopmentState::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($d) => ['label' => $d->name, 'value' => $d->id]);

        return Inertia::render('CostCenterVarieties', compact(
            'costCenters',
            'costCentersData',
            'fruits',
            'varieties',
            'rootstocks',
            'developmentStates'
        ));
    }
}
