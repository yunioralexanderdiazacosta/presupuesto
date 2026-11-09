<?php

namespace App\Http\Controllers\ProductionLots;

use App\Models\ProductionLot;
use Illuminate\Support\Facades\Auth;

class DeleteProductionLotController
{
    public function __invoke(ProductionLot $productionLot)
    {
        abort_unless($productionLot->team_id === Auth::user()->team_id, 403);

        $dispatch = $productionLot->productionDispatch;
        $productionLot->delete();
        $dispatch->syncFromLots();

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Lote eliminado correctamente.');
    }
}
