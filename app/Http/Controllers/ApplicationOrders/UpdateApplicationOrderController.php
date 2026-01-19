<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use App\Models\ApplicationOrderProduct;
use App\Models\ApplicationOrderCostCenter;
use App\Http\Requests\ApplicationOrders\UpdateApplicationOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateApplicationOrderController
{
    public function __invoke(UpdateApplicationOrderRequest $request, ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();
        
        // Validar que la orden pertenezca al equipo del usuario
        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        $validated = $request->validated();
        
        // Extraer productos y centros de costo
        $products = $validated['products'] ?? [];
        $costCenters = $validated['cost_centers'] ?? [];
        
        unset($validated['products']);
        unset($validated['cost_centers']);
        
        DB::beginTransaction();
        
        try {
            // Actualizar datos principales
            $applicationOrder->update($validated);
            
            // Calcular suma de hectáreas
            $totalHectareas = collect($costCenters)->sum('surface');
            
            // Eliminar productos y centros de costo existentes
            $applicationOrder->orderProducts()->delete();
            $applicationOrder->orderCostCenters()->delete();
            
            // Guardar nuevos productos con cálculos
            foreach ($products as $productData) {
                $cantidadPorHectarea = 0;
                $cantidadTotal = 0;
                
                if ($productData['tipo_dosis'] === 'por_hectarea') {
                    $cantidadPorHectarea = $productData['dosis_por_hectarea'];
                    $cantidadTotal = $cantidadPorHectarea * $totalHectareas;
                } elseif ($productData['tipo_dosis'] === 'por_100_litros') {
                    $hectolitros = $validated['mojamiento'] / 100;
                    $cantidadPorHectarea = $productData['dosis_por_100'] * $hectolitros;
                    $cantidadTotal = $cantidadPorHectarea * $totalHectareas;
                }
                
                ApplicationOrderProduct::create([
                    'application_order_id' => $applicationOrder->id,
                    'product_id' => $productData['product_id'],
                    'tipo_dosis' => $productData['tipo_dosis'],
                    'dosis_por_100' => $productData['dosis_por_100'] ?? null,
                    'dosis_por_hectarea' => $productData['dosis_por_hectarea'] ?? null,
                    'cantidad_por_hectarea' => $cantidadPorHectarea,
                    'cantidad_total' => $cantidadTotal,
                    'carencia' => $productData['carencia'],
                    'reingreso' => $productData['reingreso'],
                ]);
            }
            
            // Guardar nuevos centros de costo
            foreach ($costCenters as $ccData) {
                ApplicationOrderCostCenter::create([
                    'application_order_id' => $applicationOrder->id,
                    'cost_center_id' => $ccData['cost_center_id'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('application-orders.index')
                ->with('success', 'Orden de aplicación actualizada exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la orden: ' . $e->getMessage()])->withInput();
        }
    }
}
