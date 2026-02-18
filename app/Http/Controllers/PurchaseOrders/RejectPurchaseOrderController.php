<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Mail\PurchaseOrderRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class RejectPurchaseOrderController extends Controller
{
    public function __invoke(Request $request, PurchaseOrder $purchaseOrder)
    {
        // El route model binding garantiza que se procese la orden correcta del URL
        // Verificar que la orden esté en estado 'pending' (protección contra doble rechazo)
        if ($purchaseOrder->status !== 'pending') {
            $statusMessages = [
                'approved' => 'Esta orden ya fue aprobada anteriormente.',
                'rejected' => 'Esta orden ya fue rechazada anteriormente.',
                'draft' => 'Esta orden aún está en borrador.',
                'sent' => 'Esta orden ya fue enviada al proveedor.',
                'completed' => 'Esta orden ya está completada.',
                'cancelled' => 'Esta orden fue cancelada.',
            ];
            
            $message = $statusMessages[$purchaseOrder->status] ?? 'Esta orden ya no está pendiente de aprobación.';
            return view('purchase-orders.already-processed', [
                'purchaseOrder' => $purchaseOrder,
                'message' => $message,
                'action' => 'rechazar'
            ]);
        }

        // Si viene con POST (con motivo de rechazo), procesar
        if ($request->isMethod('post')) {
            $request->validate([
                'rejection_reason' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();
            try {
                // Actualizar estado a rechazado
                // No se registra quién rechazó porque el email ya identifica al aprobador
                $purchaseOrder->update([
                    'status' => 'rejected',
                    'approved_by' => null,
                ]);

                // Enviar email al solicitante notificando el rechazo
                if ($purchaseOrder->requestedBy && $purchaseOrder->requestedBy->email) {
                    Mail::to($purchaseOrder->requestedBy->email)
                        ->send(new PurchaseOrderRejected($purchaseOrder, $request->rejection_reason));
                }

                DB::commit();

                // Redirigir a página de confirmación
                return view('purchase-orders.rejected-confirmation', [
                    'purchaseOrder' => $purchaseOrder
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error al rechazar la orden: ' . $e->getMessage());
            }
        }

        // Si viene con GET, mostrar formulario para ingresar motivo de rechazo
        return view('purchase-orders.reject-form', [
            'purchaseOrder' => $purchaseOrder
        ]);
    }
}
