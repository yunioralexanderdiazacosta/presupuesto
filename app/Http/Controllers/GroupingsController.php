<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Grouping;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Log;

class GroupingsController extends Controller
{
    /**
     * Display a listing of groupings.
     */
    public function __invoke(Request $request)
    {
        $term = $request->term ?? '';

        $groupings = Grouping::with(['season', 'costCenters'])
            ->where('team_id', auth()->user()->team_id)
            ->when($term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->paginate(10);

        // Temporada activa desde sesión
        $currentSeasonId = session('season_id');

        // Filtrar los cost centers por temporada activa
        $costCenters = CostCenter::with(['fruit','variety','parcel','developmentState'])
            ->where('season_id', $currentSeasonId)
            ->get();
        Log::info('CostCenters enviados:', ['count' => $costCenters->count(), 'ids' => $costCenters->pluck('id'), 'season_id' => $currentSeasonId]);

        // Siempre enviar costCenters, aunque esté vacío
        return Inertia::render('Groupings', [
            'groupings' => $groupings,
            'term' => $term,
            'costCenters' => $costCenters ?? [],
            'currentSeasonId' => $currentSeasonId,
        ]);
    }
}
