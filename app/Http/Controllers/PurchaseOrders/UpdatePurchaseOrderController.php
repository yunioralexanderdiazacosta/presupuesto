<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Traits\CheckSeasonLocked;

class UpdatePurchaseOrderController extends Controller
{
    public function __invoke(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Verificar pertenencia (usar != para evitar problemas de tipo string vs int)
        if ((int)$purchaseOrder->team_id != (int)$user->team_id || (int)$purchaseOrder->season_id != (int)$season_id) {
            return back()->withErrors(['error' => 'No tiene permisos para editar esta orden.']);
        }

        DB::beginTransaction();
        try {
            // Calcular totales
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.19; // IVA 19%
            $total = $subtotal + $tax;

            // Actualizar orden de compra
            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'company_reason_id' => $request->company_reason_id,
                'assigned_to' => $request->assigned_to,
                'order_date' => $request->order_date,
                'delivery_date' => $request->delivery_date,
                'payment_terms' => $request->payment_terms,
                'status' => $request->status ?? $purchaseOrder->status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'notes' => $request->notes,
            ]);

            // Actualizar centros de costo
            if (isset($request->cost_center_ids)) {
                $purchaseOrder->costCenters()->sync($request->cost_center_ids);
            }

            // Si el usuario aprueba, registrar aprobador
            if ($request->status === 'approved' && !$purchaseOrder->approved_by) {
                $purchaseOrder->update(['approved_by' => $user->id]);
            }

            // Eliminar items anteriores
            $purchaseOrder->items()->delete();

            // Crear nuevos items
            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', 'Orden de compra actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar la orden de compra: ' . $e->getMessage()]);
        }
    }
}
