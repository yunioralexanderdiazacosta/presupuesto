<?php

namespace App\Http\Controllers\ProductionLots;

use App\Models\ProductionLot;
use App\Services\ProductionProcessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteProductionLotController
{
    public function __invoke(ProductionLot $productionLot, ProductionProcessService $service)
    {
        abort_unless($productionLot->team_id === Auth::user()->team_id, 403);

        $dispatch = $productionLot->productionDispatch;
        $processIds = $productionLot->allocations()->pluck('production_process_id')->all();

        DB::transaction(function () use ($productionLot, $processIds, $service) {
            $productionLot->delete();
            $service->refreshAfterLotsRemoved($processIds);
        });

        $dispatch->syncFromLots();

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Lote eliminado correctamente.');
    }
}
