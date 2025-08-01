<?php

namespace App\Http\Controllers;

use App\Models\Product2;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Product2Controller extends Controller
{
    /**
     * Display a listing of Product2.
     */
    public function index(Request $request)
    {
        // Obtener término de búsqueda (si existe)
        $term = $request->term ?? '';
        // Filtrar por nivel 3 previo si se pasa
        $level3 = $request->level3 ?? '';

        // Búsqueda y paginación similar a ProductsController
        $products2 = Product2::with('priceUnit')
            ->when($term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })->when($level3, function ($query, $l3) {
                $query->where('level3', $l3);
            })
        // Ordenar alfabéticamente por nombre
        ->orderBy('name')
        ->paginate(1000)
        ->withQueryString();

        // Si la petición es AJAX/JSON, devolver JSON para el modal
        if ($request->wantsJson()) {
            // Transformar cada elemento para agregar el nombre de la unidad
            $products2->getCollection()->transform(function ($item) {
                $data = $item->toArray();
                $data['unit'] = $item->priceUnit->name ?? '';
                return $data;
            });
            return response()->json($products2);
        }
        // Devolver la vista Inertia con resultados y término
        return Inertia::render('Products2Modal', [
            'products2' => $products2,
            'term' => $term,
        ]);
    }
}
