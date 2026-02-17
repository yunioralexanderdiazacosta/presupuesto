<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Mail\PurchaseOrderApproved;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ApprovePurchaseOrderController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        // Verificar que la orden esté en estado 'pending'
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
                'action' => 'aprobar'
            ]);
        }

        DB::beginTransaction();
        try {
            // Actualizar estado a aprobado
            $purchaseOrder->update([
                'status' => 'approved',
                'approved_by' => Auth::id() ?? null, // Si está autenticado, registrar quién aprobó
            ]);

            // Enviar email al solicitante notificando la aprobación
            if ($purchaseOrder->requestedBy && $purchaseOrder->requestedBy->email) {
                Mail::to($purchaseOrder->requestedBy->email)
                    ->send(new PurchaseOrderApproved($purchaseOrder));
            }

            DB::commit();

            // Redirigir a página de confirmación
            return view('purchase-orders.approved-confirmation', [
                'purchaseOrder' => $purchaseOrder
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('login')->with('error', 'Error al aprobar la orden: ' . $e->getMessage());
        }
    }
}
