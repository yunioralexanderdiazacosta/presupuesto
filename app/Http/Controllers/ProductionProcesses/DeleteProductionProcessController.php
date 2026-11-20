<?php

namespace App\Http\Controllers\ProductionProcesses;

use App\Models\ProductionProcess;
use App\Services\ProductionProcessService;
use Illuminate\Support\Facades\Auth;

class DeleteProductionProcessController
{
    public function __invoke(ProductionProcess $productionProcess, ProductionProcessService $service)
    {
        abort_unless($productionProcess->team_id === Auth::user()->team_id, 403);

        $service->delete($productionProcess);

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Proceso eliminado correctamente.');
    }
}
