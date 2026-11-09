<?php

namespace App\Http\Controllers\ProductionLots;

use App\Http\Requests\ProductionLots\ProcessProductionLotRequest;
use App\Models\ProductionLot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcessProductionLotController
{
    public function __invoke(ProductionLot $productionLot, ProcessProductionLotRequest $request)
    {
        abort_unless($productionLot->team_id === Auth::user()->team_id, 403);

        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $validated['status'] = 'processed';

        DB::beginTransaction();

        try {
            $productionLot->update($validated);

            // Reemplazar items del lote: eliminar anteriores y crear nuevos
            $productionLot->items()->delete();
            foreach ($items as $item) {
                $productionLot->items()->create($item + [
                    'production_dispatch_id' => $productionLot->production_dispatch_id,
                ]);
            }

            $productionLot->productionDispatch->syncFromLots();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al procesar: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Lote procesado correctamente.');
    }
}
