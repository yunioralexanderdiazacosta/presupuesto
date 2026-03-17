<?php

namespace App\Http\Controllers\CostCenterVarieties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CostCenter;
use App\Models\CostCenterVariety;
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

        $costCenters = CostCenter::withCount('costCenterVarieties')
            ->where('season_id', $season_id)
            ->whereHas('season', fn($q) => $q->where('team_id', $user->team_id))
            ->orderBy('name')
            ->get(['id', 'name', 'surface']);

        $costCentersData = $costCenters->map(fn($c) => [
            'id'              => $c->id,
            'surface'         => (float) $c->surface,
            'varieties_count' => $c->cost_center_varieties_count,
        ]);
        $costCenters = $costCenters->map(fn($c) => [
            'label'           => $c->name,
            'value'           => $c->id,
            'varieties_count' => $c->cost_center_varieties_count,
        ]);

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

        // Resumen global: ha totales y cuarteles por variedad
        $summaryByVariety = CostCenterVariety::select(
                'fruit_id', 'variety_id',
                DB::raw('SUM(surface) as total_surface'),
                DB::raw('COUNT(DISTINCT cost_center_id) as cost_center_count')
            )
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->groupBy('fruit_id', 'variety_id')
            ->with(['fruit:id,name', 'variety:id,name'])
            ->orderByDesc('total_surface')
            ->get()
            ->map(fn($r) => [
                'fruit_name'        => $r->fruit?->name ?? '—',
                'variety_name'      => $r->variety?->name ?? '—',
                'total_surface'     => round((float) $r->total_surface, 4),
                'cost_center_count' => (int) $r->cost_center_count,
            ]);

        return Inertia::render('CostCenterVarieties', compact(
            'costCenters',
            'costCentersData',
            'fruits',
            'varieties',
            'rootstocks',
            'developmentStates',
            'summaryByVariety'
        ));
    }
}
