<?php

namespace App\Http\Controllers\ApplicationOrders;

use App\Models\ApplicationOrder;
use App\Models\Product;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowApplicationOrderController
{
    public function __invoke(ApplicationOrder $applicationOrder)
    {
        $user = Auth::user();
        $seasonId = session('season_id');
        
        // Validar que la orden pertenezca al equipo del usuario
        if ($applicationOrder->team_id !== $user->team_id) {
            abort(403, 'No autorizado');
        }
        
        // Cargar todas las relaciones necesarias
        $applicationOrder->load([
            'orderProducts.product.unit',
            'orderCostCenters.costCenter',
            'team',
            'season'
        ]);
        
        // Obtener productos del equipo para el modal de edición
        $products = Product::with('unit:id,name')
            ->where('team_id', $user->team_id)
            ->get(['id', 'name', 'unit_id'])
            ->map(function($product) {
                return [
                    'value' => $product->id,
                    'label' => $product->name,
                    'unit_id' => $product->unit_id,
                    'unit_name' => $product->unit->name ?? '',
                ];
            });

        // Obtener centros de costo para el modal de edición
        $costCenters = CostCenter::where('season_id', $seasonId)
            ->whereHas('season', function($q) use ($user) {
                $q->where('team_id', $user->team_id);
            })
            ->get(['id', 'name', 'surface'])
            ->map(function($cc) {
                return [
                    'value' => $cc->id,
                    'label' => $cc->name,
                    'surface' => $cc->surface ?? 0,
                ];
            });
        
        return Inertia::render('ApplicationOrders/Show', [
            'applicationOrder' => $applicationOrder,
            'products' => $products,
            'costCenters' => $costCenters,
        ]);
    }
}
