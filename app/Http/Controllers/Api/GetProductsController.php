<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class GetProductsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        
        // Obtener productos de agroquímicos del equipo (mismo filtro que en ApplicationOrdersController)
        return Product::with('unit:id,name')
            ->whereHas('level2', function($query) {
                $query->where('name', 'agroquimicos');
            })
            ->where('team_id', $user->team_id)
            ->get(['id', 'name', 'unit_id', 'level2_id'])
            ->map(function($product) {
                return [
                    'value' => $product->id,
                    'label' => $product->name,
                    'unit_id' => $product->unit_id,
                    'unit_name' => $product->unit->name ?? '',
                ];
            });
    }
}
