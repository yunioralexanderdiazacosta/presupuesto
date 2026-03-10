<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;

class DeleteProductionDispatchController
{
    public function __invoke(ProductionDispatch $productionDispatch)
    {
        $productionDispatch->delete();

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho eliminado correctamente.');
    }
}
