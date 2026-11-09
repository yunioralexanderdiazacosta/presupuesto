<?php

namespace App\Http\Controllers\ProductionLots;

use App\Http\Requests\ProductionLots\FormProductionLotRequest;
use App\Models\ProductionLot;
use Illuminate\Support\Facades\Auth;

class UpdateProductionLotController
{
    public function __invoke(ProductionLot $productionLot, FormProductionLotRequest $request)
    {
        abort_unless($productionLot->team_id === Auth::user()->team_id, 403);

        $productionLot->update($request->validated());

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Lote actualizado correctamente.');
    }
}
