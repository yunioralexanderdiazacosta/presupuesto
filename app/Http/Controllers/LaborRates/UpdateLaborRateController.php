<?php

namespace App\Http\Controllers\LaborRates;

use App\Http\Requests\LaborRates\UpdateLaborRateRequest;
use App\Models\LaborRate;

class UpdateLaborRateController
{
    public function __invoke(UpdateLaborRateRequest $request, LaborRate $laborRate)
    {
        $laborRate->update($request->validated());

        return redirect()->back()
            ->with('success', 'Tarifa actualizada correctamente.');
    }
}
