<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use App\Traits\CheckSeasonLocked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateApplicationOrderStatusController
{
    use CheckSeasonLocked;

    public function __invoke(Request $request, ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();

        if ($applicationOrder->team_id !== $user->team_id
            || (int) $applicationOrder->season_id !== (int) session('season_id')) {
            abort(403, 'No autorizado');
        }

        $this->abortIfSeasonLocked();

        $validated = $request->validate([
            'status' => 'required|in:pendiente,completada,cancelada',
        ]);

        // Si tiene salidas, el estado lo gobierna el módulo de Salidas de Agroquímicos
        if ($applicationOrder->agrochemicalOutflows()->exists()) {
            return back()->withErrors([
                'error' => 'La orden tiene salidas de agroquímicos registradas. Revierta la aplicación desde Salidas para cambiar su estado.',
            ]);
        }

        $applicationOrder->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado de la orden actualizado.');
    }
}
