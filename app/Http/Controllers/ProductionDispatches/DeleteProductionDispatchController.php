<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Models\ProductionProcessLot;
use App\Services\ProductionProcessService;
use Illuminate\Support\Facades\DB;

class DeleteProductionDispatchController
{
    public function __invoke(ProductionDispatch $productionDispatch, ProductionProcessService $service)
    {
        $processIds = ProductionProcessLot::whereIn('production_lot_id', $productionDispatch->lots()->select('id'))
            ->pluck('production_process_id')
            ->unique()
            ->all();

        DB::transaction(function () use ($productionDispatch, $processIds, $service) {
            $productionDispatch->delete();
            $service->refreshAfterLotsRemoved($processIds);
        });

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho eliminado correctamente.');
    }
}
