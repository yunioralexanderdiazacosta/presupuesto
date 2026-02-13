<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdatePurchaseOrderStatusController extends Controller
{
    public function __invoke(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Verificar pertenencia
        if ($purchaseOrder->team_id !== $user->team_id || $purchaseOrder->season_id !== $season_id) {
            return back()->withErrors(['error' => 'No tiene permisos para modificar esta orden.']);
        }

        $request->validate([
            'status' => 'required|in:draft,pending,approved,rejected,sent,received_partial,completed,cancelled'
        ]);

        $oldStatus = $purchaseOrder->status;
        $newStatus = $request->status;

        // Lógica de transición de estados
        $allowedTransitions = [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['approved', 'rejected', 'cancelled'],
            'approved' => ['sent', 'cancelled'],
            'rejected' => ['pending', 'cancelled'], // Permitir reenvío a aprobación
            'sent' => ['received_partial', 'completed', 'cancelled'],
            'received_partial' => ['completed', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$oldStatus]) || !in_array($newStatus, $allowedTransitions[$oldStatus])) {
            return back()->withErrors(['error' => 'La transición de estado no es válida.']);
        }

        // Actualizar estado
        $updateData = ['status' => $newStatus];

        // Si se aprueba, registrar aprobador
        if ($newStatus === 'approved' && !$purchaseOrder->approved_by) {
            $updateData['approved_by'] = $user->id;
        }

        $purchaseOrder->update($updateData);

        return redirect()->route('purchase-orders.index')->with('success', 'Estado actualizado exitosamente.');
    }
}
