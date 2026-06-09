<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\CheckSeasonLocked;

class DeleteAgrochemicalOutflowController
{
    public function __invoke(AgrochemicalOutflow $agrochemicalOutflow)
    {
        $user = Auth::user();
        
        // Validar que pertenezca al equipo del usuario
        if ($agrochemicalOutflow->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        DB::beginTransaction();
        
        try {
            $applicationOrderId = $agrochemicalOutflow->application_order_id;
            
            // Eliminar agrochemical outflow
            // (los outflows y outflow_cost_centers se eliminan automáticamente por cascade)
            $agrochemicalOutflow->delete();
            
            // Verificar si quedan aplicaciones para esta orden
            $remainingApplications = AgrochemicalOutflow::where('application_order_id', $applicationOrderId)->count();
            
            // Si no quedan, cambiar el estado de vuelta a 'pendiente'
            if ($remainingApplications === 0 && $applicationOrderId) {
                ApplicationOrder::where('id', $applicationOrderId)->update(['status' => 'pendiente']);
            }
            
            DB::commit();
            
            return redirect()->route('agrochemical-outflows.index')
                ->with('success', 'Aplicación de agroquímico eliminada correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al eliminar la aplicación: ' . $e->getMessage()]);
        }
    }
}
