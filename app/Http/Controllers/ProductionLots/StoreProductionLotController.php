<?php

namespace App\Http\Controllers\ProductionLots;

use App\Http\Requests\ProductionLots\FormProductionLotRequest;
use App\Models\ProductionDispatch;
use Illuminate\Support\Facades\Auth;

class StoreProductionLotController
{
    public function __invoke(ProductionDispatch $productionDispatch, FormProductionLotRequest $request)
    {
        abort_unless($productionDispatch->team_id === Auth::user()->team_id, 403);

        $productionDispatch->lots()->create($request->validated() + [
            'team_id' => $productionDispatch->team_id,
            'season_id' => $productionDispatch->season_id,
            'status' => 'received',
        ]);

        $productionDispatch->syncFromLots();

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Lote recepcionado correctamente.');
    }
}
