<?php

namespace App\Http\Controllers\LaborRates;

use App\Models\LaborRate;

class DeleteLaborRateController
{
    public function __invoke(LaborRate $laborRate)
    {
        $laborRate->delete();

        return redirect()->route('labor-rates.index')
            ->with('success', 'Tarifa eliminada correctamente.');
    }
}
