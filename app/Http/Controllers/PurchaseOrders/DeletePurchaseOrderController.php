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

        // Verificar pertenencia
        if ($purchaseOrder->team_id !== $user->team_id || $purchaseOrder->season_id !== $season_id) {
            return back()->withErrors(['error' => 'No tiene permisos para eliminar esta orden.']);
        }

        // Solo permitir eliminación si está en borrador o pendiente
        if (!in_array($purchaseOrder->status, ['draft', 'pending'])) {
            return back()->withErrors(['error' => 'Solo se pueden eliminar órdenes en estado borrador o pendiente.']);
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')->with('success', 'Orden de compra eliminada exitosamente.');
    }
}
