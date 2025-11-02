<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Http\Requests\FuelOutflows\UpdateFuelOutflowRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateFuelOutflowController
{
    public function __invoke(UpdateFuelOutflowRequest $request, FuelOutflow $fuelOutFlow)
    {
        $validated = $request->validated();
        
        // Extraer centros de costo si existen
        $costCenters = $validated['cost_center_id'] ?? [];
        unset($validated['cost_center_id']);
        
        // Actualizar el registro principal
        $fuelOutFlow->update($validated);
        
        // Refrescar el modelo
        $fuelOutFlow->refresh();
        
        // Actualizar centros de costo
        if (!empty($costCenters)) {
            // Eliminar registros existentes
            $fuelOutFlow->costCenters()->delete();
            
            // Debug: Verificar el ID
            Log::info('FuelOutflow ID: ' . $fuelOutFlow->id);
            Log::info('Cost Centers: ' . json_encode($costCenters));
            
            // Crear los nuevos directamente con DB para evitar problemas con Eloquent
            foreach ($costCenters as $ccId) {
                Log::info('Insertando - fuel_outflow_id: ' . $fuelOutFlow->id . ', cost_center_id: ' . $ccId);
                DB::table('fuel_outflow_cost_center')->insert([
                    'fuel_outflow_id' => $fuelOutFlow->id,
                    'cost_center_id' => $ccId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // Si no hay centros de costo, eliminar todos
            $fuelOutFlow->costCenters()->delete();
        }
        
        return redirect()->route('fuel-outflows.index')->with('success', 'Consumo de combustible actualizado correctamente.');
    }
}
