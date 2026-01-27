<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Models\FertilizerOutflow;
use App\Models\Outflow;
use App\Models\FertilizerOrder;
use Illuminate\Support\Facades\DB;

class DeleteFertilizerOutflowController
{
    public function __invoke(FertilizerOutflow $fertilizerOutflow)
    {
        DB::beginTransaction();
        
        try {
            $orderId = $fertilizerOutflow->fertilizer_order_id;
            
            // Eliminar outflows relacionados (kardex)
            Outflow::where('fertilizer_outflow_id', $fertilizerOutflow->id)->delete();
            
            // Eliminar el fertilizer outflow
            $fertilizerOutflow->delete();
            
            // Verificar si quedan más aplicaciones de esta orden
            $remainingOutflows = FertilizerOutflow::where('fertilizer_order_id', $orderId)->count();
            
            // Si no quedan aplicaciones, cambiar estado a pending
            if ($remainingOutflows === 0) {
                FertilizerOrder::where('id', $orderId)->update(['status' => 'pending']);
            }
            
            DB::commit();
            
            return back()->with('success', 'Aplicación eliminada correctamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la aplicación: ' . $e->getMessage()]);
        }
    }
}
