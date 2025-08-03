<?php

namespace App\Http\Controllers\Groupings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Groupings\UpdateGroupingRequest;
use App\Models\Grouping;
use App\Models\Season;

class UpdateGroupingController extends Controller
{
    public function __invoke(Grouping $grouping, UpdateGroupingRequest $request)
    {
        // Obtener team_id a partir de la temporada seleccionada
        $season = Season::findOrFail($request->season_id);
        // Actualizar agrupamiento con season y team
        $grouping->update([
            'name' => $request->name,
            'season_id' => $request->season_id,
            'team_id' => $season->team_id,
        ]);
        // Sincronizar pivote cost_center_grouping
        $grouping->costCenters()->sync($request->input('cost_center_ids', []));
        // Retornar al listado de grupaciones
        return redirect()->route('groupings.index');
    }
}
