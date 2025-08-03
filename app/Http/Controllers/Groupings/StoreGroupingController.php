<?php

namespace App\Http\Controllers\Groupings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Http\Requests\Groupings\StoreGroupingRequest;
use App\Models\Grouping;
use App\Models\CostCenter;
use App\Models\Season;
use Illuminate\Support\Facades\Auth;

class StoreGroupingController extends Controller
{
    /**
     * Handle the incoming grouping create request.
     */
    public function __invoke(StoreGroupingRequest $request)
    {
        // Obtener el team_id a partir de la temporada seleccionada
        $season = Season::findOrFail($request->season_id);
        // Crear grouping con datos validados y asignar team_id de la season
        $grouping = Grouping::create([
            'name' => $request->name,
            'season_id' => $request->season_id,
            'team_id' => $season->team_id,
        ]);
        // Sincronizar relación muchos-a-muchos en la tabla pivote cost_center_grouping
        $grouping->costCenters()->sync($request->input('cost_center_ids', []));

        // Retornar a listado de groupings
        return redirect()->route('groupings.index');
    }
}
