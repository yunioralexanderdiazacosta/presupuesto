<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Http\Requests\ProductionDispatches\UpdateProductionDispatchRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateProductionDispatchController
{
    public function __invoke(ProductionDispatch $productionDispatch, UpdateProductionDispatchRequest $request)
    {
        abort_unless($productionDispatch->team_id === Auth::user()->team_id, 403);

        $validated = $request->validated();
        $costCenters = $validated['cost_centers'];
        unset($validated['cost_centers']);

        // Los kg de la guía son la suma de lo que aporta cada cuartel
        $validated['kg_dispatched'] = round(collect($costCenters)->sum('kg_dispatched'), 2);

        DB::transaction(function () use ($productionDispatch, $validated, $costCenters) {
            $productionDispatch->update($validated);
            $productionDispatch->costCenters()->delete();
            $productionDispatch->costCenters()->createMany($costCenters);
        });

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho actualizado correctamente.');
    }
}
