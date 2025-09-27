<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use Inertia\Inertia;

class ShowFuelOutflowController
{
    public function __invoke(FuelOutflow $fuelOutflow)
    {
        return Inertia::render('FuelOutflows/Show', [
            'fuelOutflow' => $fuelOutflow->load(['machinery', 'operator', 'costCenter'])
        ]);
    }
}
