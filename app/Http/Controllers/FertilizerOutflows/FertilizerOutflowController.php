<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Models\FertilizerOutflow;
use App\Models\FertilizerOrder;
use App\Models\Invoice;
use App\Models\Product;
use App\Traits\HasInventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FertilizerOutflowController
{
    use HasInventory;
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $outflows = FertilizerOutflow::with([
            'fertilizerOrder',
            'product.unit',
            'costCenter',
            'invoiceProduct.invoice',
            'team',
            'season'
        ])
        ->where('team_id', $teamId)
        ->where('season_id', $seasonId)
        ->orderBy('date', 'desc')
        ->orderBy('created_at', 'desc')
        ->paginate(50);

        // Obtener órdenes disponibles para ejecutar con sus productos
        $availableOrders = FertilizerOrder::with([
            'orderProducts.product.unit',
            'orderProducts.unit', // Unidad usada en la orden
            'orderCostCenters.costCenter',
            'orderIrrigationSectors.irrigationSector',
            'irrigationPump' // Bomba de riego
        ])
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->where('status', 'pendiente') // Solo órdenes pendientes
            ->orderBy('date', 'desc')
            ->get();

        // Calcular stock disponible de fertilizantes por producto
        $availableStocksByProduct = $this->getAvailableStocksByInvoiceProduct($teamId, $seasonId);

        return Inertia::render('FertilizerOutflows/Index', [
            'outflows' => $outflows,
            'availableOrders' => $availableOrders,
            'availableStocksByProduct' => $availableStocksByProduct,
        ]);
    }
}
