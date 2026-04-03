<?php

namespace App\Http\Controllers\LaborRates;

use App\Http\Requests\LaborRates\StoreLaborRateRequest;
use App\Models\LaborRate;
use Illuminate\Support\Facades\Auth;

class StoreLaborRateController
{
    public function __invoke(StoreLaborRateRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['team_id'] = $user->team_id;
        $validated['season_id'] = session('season_id');

        LaborRate::create($validated);

        return redirect()->route('labor-rates.index')
            ->with('success', 'Tarifa registrada correctamente.');
    }
}
