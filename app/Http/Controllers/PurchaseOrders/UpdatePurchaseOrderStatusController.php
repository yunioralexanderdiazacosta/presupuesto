<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Mail\PurchaseOrderPendingApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class UpdatePurchaseOrderStatusController extends Controller
{
    public function __invoke(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Verificar pertenencia (usar != en lugar de !== para evitar problemas de tipo string vs int)
        if ((int)$purchaseOrder->team_id != (int)$user->team_id || (int)$purchaseOrder->season_id != (int)$season_id) {
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

        // Enviar emails automáticamente según el estado
        if ($newStatus === 'pending') {
            // Si hay un aprobador específico asignado, enviar solo a ese usuario
            if ($purchaseOrder->assigned_to && $purchaseOrder->assignedTo && $purchaseOrder->assignedTo->email) {
                Mail::to($purchaseOrder->assignedTo->email)
                    ->send(new PurchaseOrderPendingApproval($purchaseOrder, $purchaseOrder->assignedTo->name));
            } 
            // Si no hay aprobador asignado, enviar a todos los aprobadores del equipo
            elseif (Role::where('name', 'Aprobador Compras')->exists()) {
                $approvers = User::role('Aprobador Compras')
                    ->where('team_id', $user->team_id)
                    ->whereNotNull('email')
                    ->get();

                foreach ($approvers as $approver) {
                    Mail::to($approver->email)
                        ->send(new PurchaseOrderPendingApproval($purchaseOrder, $approver->name));
                }
            }
        }

        return redirect()->route('purchase-orders.index')->with('success', 'Estado actualizado exitosamente.');
    }
}
