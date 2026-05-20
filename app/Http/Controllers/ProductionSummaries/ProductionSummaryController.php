<?php

namespace App\Http\Controllers\ProductionSummaries;

use App\Http\Controllers\Controller;
use App\Models\CostCenterVariety;
use App\Models\DevelopmentState;
use App\Models\Fruit;
use App\Models\Production;
use App\Models\ProductionSummary;
use App\Models\Variety;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductionSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Obtener variety_ids que el team tiene en CostCenterVarieties de esta temporada
        $ccvQuery = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $user->team_id);

        $varietyIds = $ccvQuery->pluck('variety_id')->unique()->values();

        // Superficie agrupada por variety_id + development_state_id
        $surfaceData = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->whereIn('variety_id', $varietyIds)
            ->selectRaw('variety_id, development_state_id, SUM(surface) as total_surface')
            ->groupBy('variety_id', 'development_state_id')
            ->get()
            ->map(fn($r) => [
                'variety_id'           => $r->variety_id,
                'development_state_id' => $r->development_state_id,
                'total_surface'        => (float) $r->total_surface,
            ])
            ->values();

        // Estados de desarrollo presentes en la temporada
        $stateIds = CostCenterVariety::where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->whereNotNull('development_state_id')
            ->pluck('development_state_id')
            ->unique();

        $developmentStates = DevelopmentState::whereIn('id', $stateIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
            ->values();

        $varieties = Variety::whereIn('id', $varietyIds)
            ->with('fruit')
            ->orderBy('name')
            ->get();

        $summaries = ProductionSummary::whereHas('production', function ($q) use ($season_id, $user) {
            $q->where('season_id', $season_id)->where('team_id', $user->team_id);
        })->get();

        $productions = Production::where('season_id', $season_id)
            ->where('team_id', $user->team_id)
            ->with('advances')
            ->get();

        $fruits = Fruit::where('team_id', $user->team_id)->get();

        return Inertia::render('ProductionSummaries/Index', [
            'varieties'         => $varieties,
            'summaries'         => $summaries,
            'productions'       => $productions,
            'fruits'            => $fruits,
            'surfaceData'       => $surfaceData,
            'developmentStates' => $developmentStates,
            'season_id'         => $season_id,
        ]);
    }
}
