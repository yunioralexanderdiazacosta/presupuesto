<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Http\Requests\FuelOutflows\StoreFuelOutflowRequest;
use Illuminate\Support\Facades\Auth;

class StoreFuelOutflowController
{
    public function __invoke(StoreFuelOutflowRequest $request)
    {
        $teamId = Auth::user()->team_id;
        $seasonId = session('season_id');
        $validated = $request->validated();
        $validated['team_id'] = $teamId;
        $validated['season_id'] = $seasonId;
        FuelOutflow::create($validated);
        return redirect()->route('fuel-outflows.index')->with('success', 'Consumo de combustible registrado correctamente.');
    }
}
