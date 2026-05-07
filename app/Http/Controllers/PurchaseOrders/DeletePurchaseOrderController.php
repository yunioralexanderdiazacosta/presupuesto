<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class DeletePurchaseOrderController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Verificar pertenencia (usar != para evitar problemas de tipo string vs int)
        if ((int)$purchaseOrder->team_id != (int)$user->team_id || (int)$purchaseOrder->season_id != (int)$season_id) {
            return back()->withErrors(['error' => 'No tiene permisos para eliminar esta orden.']);
        }

            $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')->with('success', 'Orden de compra eliminada exitosamente.');
    }
}
