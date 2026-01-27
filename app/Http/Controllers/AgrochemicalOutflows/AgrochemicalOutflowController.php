<?php

namespace App\Http\Controllers\AgrochemicalOutflows;

use App\Models\AgrochemicalOutflow;
use App\Models\ApplicationOrder;
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

        return Inertia::render('AgrochemicalOutflows/Index', [
            'outflows' => $outflows,
            'availableOrders' => $availableOrders,
            'availableStocksByProduct' => $availableStocksByProduct,
        ]);
    }
}
