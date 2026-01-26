<?php

namespace App\Http\Controllers\FertilizerOrders;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOrder;
use Illuminate\Support\Facades\Auth;

class DeleteFertilizerOrderController extends Controller
{
    public function __invoke(FertilizerOrder $fertilizerOrder)
    {
        $user = Auth::user();

        if ($fertilizerOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }

        try {
            // Las relaciones se eliminan automáticamente por cascadeOnDelete
            $fertilizerOrder->delete();
            return redirect()->route('fertilizer-orders.index')->with('success', 'Orden de fertilización eliminada correctamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la orden: ' . $e->getMessage()]);
        }
    }
}
