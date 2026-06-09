<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CompanyReason;

use App\Traits\CheckSeasonLocked;

class StorePurchaseOrderController extends Controller
{
    public function __invoke(StorePurchaseOrderRequest $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        DB::beginTransaction();
        try {
            // Generar número de orden correlativo (global para evitar duplicados)
            $prefix = 'PO-' . date('Y') . '-';
            $lastOrder = PurchaseOrder::where('order_number', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING(order_number, -5) AS UNSIGNED) DESC')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastOrder ? intval(substr($lastOrder->order_number, -5)) + 1 : 1;
            $orderNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // Calcular totales
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.19; // IVA 19%
            $total = $subtotal + $tax;

            // Crear orden de compra
            $purchaseOrder = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'supplier_id' => $request->supplier_id,
                'company_reason_id' => $request->company_reason_id,
                'season_id' => $season_id,
                'team_id' => $user->team_id,
                'status' => 'draft',
                'requested_by' => $user->id,
                'assigned_to' => $request->assigned_to,
                'order_date' => $request->order_date,
                'delivery_date' => $request->delivery_date,
                'payment_terms' => $request->payment_terms,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'notes' => $request->notes,
            ]);

            // Asociar centros de costo (si hay)
            if (!empty($request->cost_center_ids)) {
                $purchaseOrder->costCenters()->attach($request->cost_center_ids);
            }

            // Crear items de la orden
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

            return redirect()->route('purchase-orders.index')->with('success', 'Orden de compra creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al crear la orden de compra: ' . $e->getMessage()]);
        }
    }
}
