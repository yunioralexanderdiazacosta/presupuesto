<?php

namespace App\Http\Controllers\ProductionDispatches;

use App\Models\ProductionDispatch;
use App\Http\Requests\ProductionDispatches\ProcessProductionDispatchRequest;
use Illuminate\Support\Facades\DB;

class ProcessProductionDispatchController
{
    public function __invoke(ProductionDispatch $productionDispatch, ProcessProductionDispatchRequest $request)
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $validated['status'] = 'processed';

        DB::beginTransaction();

        try {
            $productionDispatch->update($validated);

            // Reemplazar items: eliminar anteriores y crear nuevos
            $productionDispatch->items()->delete();
            foreach ($items as $item) {
                $productionDispatch->items()->create($item);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al procesar: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Despacho procesado correctamente.');
    }
}
