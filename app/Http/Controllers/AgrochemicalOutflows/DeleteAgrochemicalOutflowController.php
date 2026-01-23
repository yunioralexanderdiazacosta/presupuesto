<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use Illuminate\Support\Facades\Auth;

class DeleteAgrochemicalOutflowController
{
    public function __invoke(AgrochemicalOutflow $agrochemicalOutflow)
    {
        $user = Auth::user();
        
        // Validar que pertenezca al equipo del usuario
        if ($agrochemicalOutflow->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        // Guardar el ID de la orden antes de eliminar
        $applicationOrderId = $agrochemicalOutflow->application_order_id;
        
        // Eliminar el outflow asociado (se eliminará por cascada)
        if ($agrochemicalOutflow->outflow) {
            $agrochemicalOutflow->outflow->costCenters()->delete();
            $agrochemicalOutflow->outflow->delete();
        }
        
        // Eliminar agrochemical outflow
        $agrochemicalOutflow->delete();
        
        // Verificar si quedan aplicaciones para esta orden
        // Si no quedan, cambiar el estado de vuelta a 'pendiente'
        if ($applicationOrderId) {
            $remainingApplications = AgrochemicalOutflow::where('application_order_id', $applicationOrderId)->count();
            
            if ($remainingApplications === 0) {
                ApplicationOrder::find($applicationOrderId)->update(['status' => 'pendiente']);
            }
        }
        
        return redirect()->route('agrochemical-outflows.index')
            ->with('success', 'Aplicación de agroquímico eliminada correctamente.');
    }
}
