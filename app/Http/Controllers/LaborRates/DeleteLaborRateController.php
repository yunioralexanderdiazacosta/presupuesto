<?php

namespace App\Http\Controllers\LaborRates;

use App\Models\LaborRate;

class DeleteLaborRateController
{
    public function __invoke(LaborRate $laborRate)
    {
        $laborRate->delete();

        return redirect()->back()
            ->with('success', 'Trato eliminado correctamente.');
    }
}
