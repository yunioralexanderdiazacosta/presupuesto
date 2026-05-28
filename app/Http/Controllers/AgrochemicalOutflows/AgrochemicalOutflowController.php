<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Product;
use App\Traits\HasInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AgrochemicalOutflowController
{
    use HasInventory;
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $outflows = AgrochemicalOutflow::with([
            'applicationOrder',
            'product.unit',
            'costCenter',
            'invoiceProduct.invoice.companyReason',
        ])
        ->where('team_id', $teamId)
        ->where('season_id', $seasonId)
        ->orderBy('date', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();

        // Agrupar outflows por orden de aplicación (1 fila = 1 orden)
        $groupedOutflows = $outflows->groupBy('application_order_id')->map(function ($group) {
            $first = $group->first();
            $productos = $group->pluck('product.name')->unique()->implode(', ');
            $cuartelesList = $group->pluck('costCenter.name')->unique()->values()->toArray();
            $cuarteles = implode(', ', $cuartelesList);
            $facturas = $group->pluck('invoiceProduct.invoice.number_document')->unique()->filter()->implode(', ');
            $razonesSociales = $group->pluck('invoiceProduct.invoice.companyReason.name')->unique()->filter()->implode(', ');
            $cantidadTotal = $group->sum('quantity');
            $unidad = $first->product->unit->name ?? '';

            // Detalle por cuartel para la vista expandida
            $detalle = $group->map(fn($o) => [
                'id' => $o->id,
                'cuartel' => $o->costCenter->name ?? '-',
                'producto' => $o->product->name ?? '-',
                'cantidad' => $o->quantity,
                'unidad' => $o->product->unit->name ?? '',
                'factura' => $o->invoiceProduct->invoice->number_document ?? 'N/A',
            ])->values();

            return [
                'application_order_id' => $first->application_order_id,
                'date' => $first->date,
                'maquinadas' => $first->maquinadas,
                'productos' => $productos,
                'cuarteles' => $cuarteles,
                'cuarteles_list' => $cuartelesList,
                'cantidad_total' => $cantidadTotal,
                'unidad' => $unidad,
                'facturas' => $facturas ?: 'N/A',
                'razones_sociales' => $razonesSociales ?: '-',
                'outflow_ids' => $group->pluck('id')->toArray(),
                'observations' => $first->observations,
                'detalle' => $detalle,
            ];
        })->values();

        // Obtener órdenes disponibles para ejecutar con sus productos
        $availableOrders = ApplicationOrder::with([
            'orderProducts.product.unit',
            'orderProducts.unit', // Unidad usada en la orden
            'orderCostCenters.costCenter'
        ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', 'pendiente') // Solo órdenes pendientes
            ->orderBy('date', 'desc')
            ->get();

        // Calcular stock disponible de agroquímicos por producto
        $availableStocksByProduct = $this->getAvailableStocksByInvoiceProduct($teamId, $seasonId);

        $branches = Branch::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name]);

        return Inertia::render('AgrochemicalOutflows/Index', [
            'outflows' => $groupedOutflows,
            'availableOrders' => $availableOrders,
            'availableStocksByProduct' => $availableStocksByProduct,
            'branches' => $branches,
        ]);
    }
}
