<?php

namespace App\Http\Controllers\ProductionProcesses;

use App\Http\Requests\ProductionProcesses\FormProductionProcessRequest;
use App\Services\ProductionProcessService;
use Illuminate\Support\Facades\Auth;

class StoreProductionProcessController
{
    public function __invoke(FormProductionProcessRequest $request, ProductionProcessService $service)
    {
        $user = Auth::user();

        try {
            $service->save(null, $request->validated(), $user->team_id, session('season_id'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('production-dispatches.index')
            ->with('success', 'Proceso registrado correctamente.');
    }
}
