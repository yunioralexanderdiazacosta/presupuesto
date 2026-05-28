<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateProductCarenciaReingresoController extends Controller
{
    public function __invoke(Request $request, Product $product)
    {
        $user = Auth::user();

        // Verificar que el producto pertenece al equipo del usuario
        if ($product->team_id !== $user->team_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'carencia'  => 'nullable|integer|min:0',
            'reingreso' => 'nullable|integer|min:0',
        ]);

        $product->carencia  = $request->carencia;
        $product->reingreso = $request->reingreso;
        $product->save();

        return response()->json([
            'id'        => $product->id,
            'carencia'  => $product->carencia,
            'reingreso' => $product->reingreso,
        ]);
    }
}
