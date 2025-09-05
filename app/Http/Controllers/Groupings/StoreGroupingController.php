<?php

namespace App\Http\Controllers\Groupings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Http\Requests\Groupings\StoreGroupingRequest;
use App\Models\Grouping;
use App\Models\CostCenter;
use App\Models\Season;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StoreGroupingController extends Controller
{
    /**
     * Handle the incoming grouping create request.
     */
    public function __invoke(StoreGroupingRequest $request)
    {
       

    // Log para depuración: registrar datos de petición
    Log::info('StoreGroupingController invoke', $request->all());
    // Obtener el team_id del usuario autenticado
    $user = Auth::user();
        $grouping = Grouping::create([
            'name' => $request->name,
            'season_id' => $request->season_id,
            'team_id' => $user->team_id,
        ]);
        // Sincronizar relación muchos-a-muchos en la tabla pivote cost_center_grouping
        $grouping->costCenters()->sync($request->input('cost_center_ids', []));

        // Retornar a listado de groupings
        return redirect()->route('groupings.index');
    }
}
