<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use Illuminate\Support\Facades\Auth;

use App\Traits\CheckSeasonLocked;

class DeleteApplicationOrderController
{
    public function __invoke(ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();
        
        // Validar que la orden pertenezca al equipo del usuario
        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        try {
            $applicationOrder->delete();
            
            return redirect()->route('application-orders.index')
                ->with('success', 'Orden de aplicación eliminada exitosamente.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la orden: ' . $e->getMessage()]);
        }
    }
}
