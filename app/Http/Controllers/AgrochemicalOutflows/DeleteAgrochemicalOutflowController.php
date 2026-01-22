<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
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
        
        // Eliminar el outflow asociado (se eliminará por cascada)
        if ($agrochemicalOutflow->outflow) {
            $agrochemicalOutflow->outflow->costCenters()->delete();
            $agrochemicalOutflow->outflow->delete();
        }
        
        // Eliminar agrochemical outflow
        $agrochemicalOutflow->delete();
        
        return redirect()->route('agrochemical-outflows.index')
            ->with('success', 'Aplicación de agroquímico eliminada correctamente.');
    }
}
