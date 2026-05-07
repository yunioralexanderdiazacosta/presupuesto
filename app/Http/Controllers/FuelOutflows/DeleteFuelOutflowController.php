<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteFuelOutflowController
{
    public function __invoke(FuelOutflow $fuelOutFlow)
    {
        Log::info('[DeleteFuelOutflow] Iniciando delete', ['id' => $fuelOutFlow->id]);

        DB::beginTransaction();
        try {
            // Eliminar el registro de Outflow relacionado (kardex) si existe
            if ($fuelOutFlow->outflow) {
                Log::info('[DeleteFuelOutflow] Eliminando outflow', ['outflow_id' => $fuelOutFlow->outflow->id]);
                $fuelOutFlow->outflow->costCenters()->delete();
                $fuelOutFlow->outflow->delete();
            }

            // Eliminar los centros de costo del fuel_outflow
            $fuelOutFlow->costCenters()->delete();

            // Eliminar el fuel outflow
            $fuelOutFlow->delete();

            DB::commit();
            Log::info('[DeleteFuelOutflow] Delete exitoso', ['id' => $fuelOutFlow->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DeleteFuelOutflow] ERROR', ['id' => $fuelOutFlow->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo eliminar el registro: ' . $e->getMessage()]);
        }

        return redirect()->route('fuel-outflows.index')->with('success', 'Registro eliminado correctamente.');
    }
}
