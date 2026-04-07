<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevertAgrochemicalOutflowController
{
    public function __invoke(ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();

        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        DB::beginTransaction();

        try {
            // Eliminar todos los agrochemical outflows de esta orden
            // (los outflows y outflow_cost_centers se eliminan por cascade)
            AgrochemicalOutflow::where('application_order_id', $applicationOrder->id)
                ->where('team_id', $user->team_id)
                ->delete();

            // Revertir orden a pendiente
            $applicationOrder->update(['status' => 'pendiente']);

            DB::commit();

            return redirect()->route('agrochemical-outflows.index')
                ->with('success', 'Aplicación revertida. La orden está disponible para re-ejecutar.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al revertir: ' . $e->getMessage()]);
        }
    }
}
