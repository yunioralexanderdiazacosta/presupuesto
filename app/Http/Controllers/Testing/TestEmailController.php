<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Mail\PurchaseOrderPendingApproval;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderRejected;
use Illuminate\Support\Facades\Mail;

class TestEmailController extends Controller
{
    public function __invoke()
    {
        // Obtener la primera orden de compra disponible
        $purchaseOrder = PurchaseOrder::with(['items.product', 'items.unit', 'supplier', 'requestedBy'])
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'error' => 'No hay órdenes de compra para probar. Crea una orden primero.'
            ], 404);
        }

        // Simular datos de aprobación
        if (!$purchaseOrder->approved_by) {
            $purchaseOrder->approved_by = 1; // ID de ejemplo
        }

        return view('testing.email-previews', [
            'purchaseOrder' => $purchaseOrder,
            'emails' => [
                'pending' => new PurchaseOrderPendingApproval($purchaseOrder, 'Juan Pérez'),
                'approved' => new PurchaseOrderApproved($purchaseOrder, 'María González'),
                'rejected' => new PurchaseOrderRejected($purchaseOrder, 'Este es un motivo de prueba para el rechazo', 'María González'),
            ]
        ]);
    }

    public function preview($type)
    {
        $purchaseOrder = PurchaseOrder::with(['items.product', 'items.unit', 'supplier', 'requestedBy'])
            ->first();

        if (!$purchaseOrder) {
            return 'No hay órdenes de compra. Crea una primero.';
        }

        // Simular approved_by si no existe
        if (!$purchaseOrder->approved_by) {
            $purchaseOrder->approved_by = 1;
        }

        $mailable = match($type) {
            'pending' => new PurchaseOrderPendingApproval($purchaseOrder, 'Juan Pérez'),
            'approved' => new PurchaseOrderApproved($purchaseOrder, 'María González'),
            'rejected' => new PurchaseOrderRejected($purchaseOrder, 'Presupuesto insuficiente', 'María González'),
            default => abort(404)
        };

        return $mailable->render();
    }

    public function send($type)
    {
        $purchaseOrder = PurchaseOrder::with(['items.product', 'items.unit', 'supplier', 'requestedBy'])
            ->first();

        if (!$purchaseOrder) {
            return response()->json(['error' => 'No hay órdenes de compra'], 404);
        }

        // Simular approved_by
        if (!$purchaseOrder->approved_by) {
            $purchaseOrder->approved_by = 1;
        }

        $mailable = match($type) {
            'pending' => new PurchaseOrderPendingApproval($purchaseOrder, 'Juan Pérez'),
            'approved' => new PurchaseOrderApproved($purchaseOrder, 'María González'),
            'rejected' => new PurchaseOrderRejected($purchaseOrder, 'Motivo de prueba', 'María González'),
            default => abort(404)
        };

        Mail::to('test@example.com')->send($mailable);

        return response()->json([
            'success' => true,
            'message' => 'Email enviado! Revisa storage/logs/laravel.log',
            'type' => $type,
            'order' => $purchaseOrder->order_number
        ]);
    }
}
