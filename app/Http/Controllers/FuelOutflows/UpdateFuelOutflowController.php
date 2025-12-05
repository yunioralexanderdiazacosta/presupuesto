<?php

namespace App\Http\Controllers\FuelOutflows;

use App\Models\FuelOutflow;
use App\Models\Outflow;
use App\Models\Product;
use App\Http\Requests\FuelOutflows\UpdateFuelOutflowRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateFuelOutflowController
{
    public function __invoke(UpdateFuelOutflowRequest $request, FuelOutflow $fuelOutFlow)
    {
        $validated = $request->validated();
        
        // 🔥 DEBUG: Ver qué datos llegan
        Log::info('=== UPDATE FUEL OUTFLOW DEBUG ===');
        Log::info('Validated data:', $validated);
        Log::info('Invoice Product ID: ' . ($validated['invoice_product_id'] ?? 'NULL'));
        Log::info('Credit Debit Note Item ID: ' . ($validated['credit_debit_note_item_id'] ?? 'NULL'));
        
        // Extraer centros de costo si existen
        $costCenters = $validated['cost_center_id'] ?? [];
        unset($validated['cost_center_id']);
        
        DB::beginTransaction();
        
        try {
            // 1. Actualizar el registro principal de FuelOutflow
            $fuelOutFlow->update($validated);
            $fuelOutFlow->refresh();
            
            // 2. Obtener level3_id de combustible desde el producto
            $product = Product::findOrFail($validated['product_id']);
            $level3Id = $product->level3_id;
            
            // 3. Actualizar o crear el registro en Outflow
            $outflow = $fuelOutFlow->outflow;
            
            if ($outflow) {
                // Si existe el Outflow, actualizarlo
                $outflow->update([
                    'user_id' => auth()->id(),
                    'invoice_product_id' => $validated['invoice_product_id'] ?? null,
                    'credit_debit_note_item_id' => $validated['credit_debit_note_item_id'] ?? null,
                    'machinery_id' => $validated['machinery_id'] ?? null,
                    'project_id' => $validated['project_id'] ?? null,
                    'operation_id' => $validated['operation_id'] ?? null,
                    'quantity' => $validated['liters'],
                    'date' => $validated['date'],
                    'level3_id' => $level3Id,
                    'notes' => 'Consumo de combustible - ' . ($validated['observations'] ?? 'Sin observaciones'),
                ]);
            } else {
                // Si no existe, crearlo (caso de registros antiguos)
                $outflow = Outflow::create([
                    'fuel_outflow_id' => $fuelOutFlow->id,
                    'team_id' => $fuelOutFlow->team_id,
                    'season_id' => $fuelOutFlow->season_id,
                    'user_id' => auth()->id(),
                    'invoice_product_id' => $validated['invoice_product_id'] ?? null,
                    'credit_debit_note_item_id' => $validated['credit_debit_note_item_id'] ?? null,
                    'machinery_id' => $validated['machinery_id'] ?? null,
                    'project_id' => $validated['project_id'] ?? null,
                    'operation_id' => $validated['operation_id'] ?? null,
                    'quantity' => $validated['liters'],
                    'date' => $validated['date'],
                    'level3_id' => $level3Id,
                    'notes' => 'Consumo de combustible - ' . ($validated['observations'] ?? 'Sin observaciones'),
                ]);
            }
            
            // 4. Actualizar centros de costo en outflow_cost_center (asociados al Outflow)
            $outflow->costCenters()->delete();
            
            if (!empty($costCenters)) {
                foreach ($costCenters as $ccId) {
                    $outflow->costCenters()->create([
                        'cost_center_id' => $ccId,
                        'observations' => $validated['observations'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors([
                'error' => 'Error al actualizar: ' . $e->getMessage()
            ])->withInput();
        }
        
        return redirect()->route('fuel-outflows.index')->with('success', 'Consumo de combustible actualizado correctamente.');
    }
}
