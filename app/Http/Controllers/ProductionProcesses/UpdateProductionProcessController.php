<?php

namespace App\Http\Controllers\ProductionProcesses;

use App\Http\Requests\ProductionProcesses\FormProductionProcessRequest;
use App\Models\ProductionProcess;
use App\Services\ProductionProcessService;
use Illuminate\Support\Facades\Auth;

class UpdateProductionProcessController
{
    public function __invoke(ProductionProcess $productionProcess, FormProductionProcessRequest $request, ProductionProcessService $service)
    {
        $user = Auth::user();
        abort_unless($productionProcess->team_id === $user->team_id, 403);

        try {
            $service->save($productionProcess, $request->validated(), $user->team_id, $productionProcess->season_id);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Proceso actualizado correctamente.');
    }
}
