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
            ->when($term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->paginate(10);

        // Obtener todos los cost centers con sus relaciones para fruta, variedad, parcela y estado de desarrollo
        $costCenters = CostCenter::with(['fruit','variety','parcel','development_state'])->get();
        Log::info('CostCenters enviados:', ['count' => $costCenters->count(), 'ids' => $costCenters->pluck('id')]);
        // Temporada activa desde sesión
        $currentSeasonId = session('season_id');

        // Siempre enviar costCenters, aunque esté vacío
        return Inertia::render('Groupings', [
            'groupings' => $groupings,
            'term' => $term,
            'costCenters' => $costCenters ?? [],
        ]);
    }
}
