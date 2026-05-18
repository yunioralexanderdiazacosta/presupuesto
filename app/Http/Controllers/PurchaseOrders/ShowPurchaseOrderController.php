<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowPurchaseOrderController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Verificar pertenencia (usar != para evitar problemas de tipo string vs int)
        if ((int)$purchaseOrder->team_id != (int)$user->team_id || (int)$purchaseOrder->season_id != (int)$season_id) {
            abort(403, 'No tiene permisos para ver esta orden.');
        }

        // Cargar relaciones
        $purchaseOrder->load([
            'supplier',
            'companyReason',
            'costCenters',
            'requestedBy',
            'approvedBy',
            'items.product.unit',
            'items.unit'
        ]);

        $orderData = [
            'id' => $purchaseOrder->id,
            'order_number' => $purchaseOrder->order_number,
            'order_date' => $purchaseOrder->order_date ? $purchaseOrder->order_date->format('Y-m-d') : null,
            'delivery_date' => $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format('Y-m-d') : null,
            'supplier' => $purchaseOrder->supplier ? [
                'id' => $purchaseOrder->supplier->id,
                'name' => $purchaseOrder->supplier->name,
                'rut' => $purchaseOrder->supplier->rut ?? '',
                'contact' => $purchaseOrder->supplier->contact ?? '',
                'email' => $purchaseOrder->supplier->email ?? '',
                'phone' => $purchaseOrder->supplier->phone ?? '',
            ] : null,
            'company_reason' => $purchaseOrder->companyReason ? [
                'id' => $purchaseOrder->companyReason->id,
                'name' => $purchaseOrder->companyReason->name,
                'rut' => $purchaseOrder->companyReason->rut ?? '',
            ] : null,
            'cost_centers' => $purchaseOrder->costCenters->map(function($cc) {
                return [
                    'id' => $cc->id,
                    'name' => $cc->name
                ];
            }),
            'status' => $purchaseOrder->status,
            'status_label' => $purchaseOrder->status_label,
            'status_color' => $purchaseOrder->status_color,
            'payment_terms' => $purchaseOrder->payment_terms,
            'subtotal' => $purchaseOrder->subtotal,
            'tax' => $purchaseOrder->tax,
            'total' => $purchaseOrder->total,
            'notes' => $purchaseOrder->notes,
            'requested_by' => $purchaseOrder->requestedBy->name ?? '',
            'approved_by' => $purchaseOrder->approvedBy->name ?? '',
            'created_at' => $purchaseOrder->created_at ? \Carbon\Carbon::parse($purchaseOrder->created_at)->format('d-m-Y H:i') : null,
            'items' => $purchaseOrder->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id ?? null,
                        'name' => $item->product->name ?? 'Producto no encontrado',
                    ],
                    'quantity' => $item->quantity,
                    'unit' => [
                        'id' => $item->unit->id ?? null,
                        'name' => $item->unit->name ?? 'Unidad no encontrada',
                    ],
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                ];
            }),
        ];

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $orderData,
        ]);
    }
}
