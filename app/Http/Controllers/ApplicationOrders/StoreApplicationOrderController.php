<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use App\Models\ApplicationOrderProduct;
use App\Models\ApplicationOrderCostCenter;
use App\Http\Requests\ApplicationOrders\StoreApplicationOrderRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreApplicationOrderController
{
    public function __invoke(StoreApplicationOrderRequest $request)
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');
        
        $validated = $request->validated();
        $validated['team_id'] = $teamId;
        $validated['season_id'] = $seasonId;
        
        // Extraer productos y centros de costo del array validado
        $products = $validated['products'] ?? [];
        $costCenters = $validated['cost_centers'] ?? [];
        
        unset($validated['products']);
        unset($validated['cost_centers']);
        
        DB::beginTransaction();
        
        try {
            // Crear la orden de aplicación
            $applicationOrder = ApplicationOrder::create($validated);
            
            // Calcular suma de hectáreas de los centros de costo seleccionados
            $totalHectareas = collect($costCenters)->sum('surface');
            
            // Guardar productos con cálculos
            foreach ($products as $productData) {
                $cantidadPorHectarea = 0;
                $cantidadTotal = 0;
                
                // Obtener el producto para usar su unidad base
                $product = \App\Models\Product::findOrFail($productData['product_id']);
                
                if ($productData['tipo_dosis'] === 'por_hectarea') {
                    // Cálculo directo por hectárea
                    $cantidadPorHectarea = $productData['dosis_por_hectarea'];
                    $cantidadTotal = $cantidadPorHectarea * $totalHectareas;
                } elseif ($productData['tipo_dosis'] === 'por_100_litros') {
                    // Cálculo por 100 litros
                    $hectolitros = $validated['mojamiento'] / 100;
                    $cantidadPorHectarea = $productData['dosis_por_100'] * $hectolitros;
                    $cantidadTotal = $cantidadPorHectarea * $totalHectareas;
                }
                
                ApplicationOrderProduct::create([
                    'application_order_id' => $applicationOrder->id,
                    'product_id' => $productData['product_id'],
                    'unit_id' => $product->unit_id,
                    'tipo_dosis' => $productData['tipo_dosis'],
                    'dosis_por_100' => $productData['dosis_por_100'] ?? null,
                    'dosis_por_hectarea' => $productData['dosis_por_hectarea'] ?? null,
                    'cantidad_por_hectarea' => $cantidadPorHectarea,
                    'cantidad_total' => $cantidadTotal,
                    'carencia' => $productData['carencia'],
                    'reingreso' => $productData['reingreso'],
                ]);
            }
            
            // Guardar centros de costo
            foreach ($costCenters as $ccData) {
                ApplicationOrderCostCenter::create([
                    'application_order_id' => $applicationOrder->id,
                    'cost_center_id' => $ccData['cost_center_id'],
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('application-orders.index')
                ->with('success', 'Orden de aplicación creada exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la orden: ' . $e->getMessage()])->withInput();
        }
    }
}
