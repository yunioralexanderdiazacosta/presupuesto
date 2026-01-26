<?php

namespace App\Http\Controllers\IrrigationPumps;

use App\Http\Controllers\Controller;
use App\Models\IrrigationPump;
use Illuminate\Support\Facades\Auth;

class DeleteIrrigationPumpController extends Controller
{
    public function __invoke(IrrigationPump $irrigationPump)
    {
        $user = Auth::user();

        // Verificar que pertenece al equipo
        if ($irrigationPump->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        try {
            // Los sectores se eliminan automáticamente por cascadeOnDelete
            $irrigationPump->delete();
            return redirect()->route('irrigation-pumps.index')->with('success', 'Bomba de riego eliminada correctamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la bomba: ' . $e->getMessage()]);
        }
    }
}
