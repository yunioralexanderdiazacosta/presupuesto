<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Http\Requests\FuelOutflows\UpdateFuelOutflowRequest;

class UpdateFuelOutflowController
{
    public function __invoke(UpdateFuelOutflowRequest $request, FuelOutflow $fuelOutflow)
    {
        $validated = $request->validated();
        $fuelOutflow->update($validated);
        return redirect()->route('fuel-outflows.index')->with('success', 'Consumo de combustible actualizado correctamente.');
    }
}
