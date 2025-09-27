<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;

class DeleteFuelOutflowController
{
    public function __invoke(FuelOutflow $fuelOutflow)
    {
        $fuelOutflow->delete();
        return redirect()->route('fuel-outflows.index')->with('success', 'Registro eliminado correctamente.');
    }
}
