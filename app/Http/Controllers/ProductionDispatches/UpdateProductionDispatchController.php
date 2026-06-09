<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Http\Requests\ProductionDispatches\UpdateProductionDispatchRequest;

use App\Traits\CheckSeasonLocked;

class UpdateProductionDispatchController
{
    public function __invoke(ProductionDispatch $productionDispatch, UpdateProductionDispatchRequest $request)
    {
        $productionDispatch->update($request->validated());

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho actualizado correctamente.');
    }
}
