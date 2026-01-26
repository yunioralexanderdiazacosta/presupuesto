<?php

namespace App\Http\Controllers\FertilizerOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormFertilizerOrderRequest;
use App\Models\FertilizerOrder;
use App\Models\FertilizerOrderProduct;
use App\Models\FertilizerOrderIrrigationSector;
use App\Models\FertilizerOrderCostCenter;
use App\Models\IrrigationSector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateFertilizerOrderController extends Controller
{
    public function __invoke(FormFertilizerOrderRequest $request, FertilizerOrder $fertilizerOrder)
    {
        $user = Auth::user();

        if ($fertilizerOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        DB::beginTransaction();
        try {
            // Actualizar la orden
            $fertilizerOrder->update([
                'date' => $request->date,
                'irrigation_pump_id' => $request->irrigation_pump_id,
                'responsable' => $request->responsable,
                'observations' => $request->observations,
            ]);

            // Eliminar relaciones existentes
            $fertilizerOrder->orderProducts()->delete();
            $fertilizerOrder->orderIrrigationSectors()->delete();
            $fertilizerOrder->orderCostCenters()->delete();

            // Recalcular superficie total
            $totalSurface = 0;
            if ($request->has('irrigation_sectors') && is_array($request->irrigation_sectors)) {
                foreach ($request->irrigation_sectors as $sectorId) {
                    $sector = IrrigationSector::find($sectorId);
                    if ($sector) {
                        FertilizerOrderIrrigationSector::create([
                            'fertilizer_order_id' => $fertilizerOrder->id,
                            'irrigation_sector_id' => $sector->id,
                            'surface' => $sector->surface,
                        ]);
                        $totalSurface += $sector->surface;
                    }
                }
            }

            // Recrear productos
            if ($request->has('products') && is_array($request->products)) {
                foreach ($request->products as $productData) {
                    $cantidadTotal = $productData['dosis_por_hectarea'] * $totalSurface;
                    
                    FertilizerOrderProduct::create([
                        'fertilizer_order_id' => $fertilizerOrder->id,
                        'product_id' => $productData['product_id'],
                        'dosis_por_hectarea' => $productData['dosis_por_hectarea'],
                        'cantidad_total' => $cantidadTotal,
                        'unit_id' => $productData['unit_id'] ?? null,
                    ]);
                }
            }

            // Recrear centros de costo
            if ($request->has('cost_centers') && is_array($request->cost_centers)) {
                foreach ($request->cost_centers as $costCenterId) {
                    FertilizerOrderCostCenter::create([
                        'fertilizer_order_id' => $fertilizerOrder->id,
                        'cost_center_id' => $costCenterId,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('fertilizer-orders.index')->with('success', 'Orden de fertilización actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la orden: ' . $e->getMessage()]);
        }
    }
}
