<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Http\Requests\ProductionDispatches\StoreProductionDispatchRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\CheckSeasonLocked;

class StoreProductionDispatchController
{
    public function __invoke(StoreProductionDispatchRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $costCenters = $validated['cost_centers'];
        unset($validated['cost_centers']);

        $validated['team_id'] = $user->team_id;
        $validated['season_id'] = session('season_id');
        $validated['status'] = 'dispatched';
        // Los kg de la guía son la suma de lo que aporta cada cuartel
        $validated['kg_dispatched'] = round(collect($costCenters)->sum('kg_dispatched'), 2);

        DB::transaction(function () use ($validated, $costCenters) {
            $dispatch = ProductionDispatch::create($validated);
            $dispatch->costCenters()->createMany($costCenters);
        });

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho registrado correctamente.');
    }
}
