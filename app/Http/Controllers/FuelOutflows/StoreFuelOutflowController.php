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
        $costCenters = $validated['cost_center_id'] ?? [];
        unset($validated['cost_center_id']);
        $fuelOutflow = FuelOutflow::create($validated);
        if (!empty($costCenters)) {
            // Eliminar registros existentes (por si acaso)
            $fuelOutflow->costCenters()->delete();
            // Crear los nuevos
            foreach ($costCenters as $ccId) {
                $fuelOutflow->costCenters()->create([
                    'cost_center_id' => $ccId,
                ]);
            }
        }
        return redirect()->route('fuel-outflows.index')->with('success', 'Consumo de combustible registrado correctamente.');
    }
}
