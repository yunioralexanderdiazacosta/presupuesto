<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Http\Requests\ProductionDispatches\StoreProductionDispatchRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductionDispatchController
{
    public function __invoke(StoreProductionDispatchRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $validated['team_id'] = $user->team_id;
        $validated['season_id'] = session('season_id');
        $validated['status'] = 'dispatched';

        ProductionDispatch::create($validated);

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho registrado correctamente.');
    }
}
