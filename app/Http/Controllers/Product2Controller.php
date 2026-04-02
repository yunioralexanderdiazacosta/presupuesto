<?php

namespace App\Http\Controllers;

use App\Models\Product2;
use Illuminate\Http\Request;
use Inertia\Inertia;


use App\Models\Unit;
use Illuminate\Support\Facades\Auth;

class Product2Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin')->except('index');
    }

    /**
     * Display a listing of Product2.
     */
    public function index(Request $request)
    {
        $term = $request->term ?? '';
        $level3 = $request->level3 ?? '';
        $form = $request->form ?? '';

        $products2 = Product2::with('priceUnit')
            ->when($term, function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when($level3, function ($query, $l3) {
                $query->where('level3', $l3);
            })
            ->when($form, function ($query, $f) {
                $query->where(function($q) use ($f) {
                    $q->whereNull('form')->orWhere('form', $f);
                });
            })
            ->orderBy('name')
            ->paginate(1000)
            ->withQueryString();

        // Si es petición AJAX (desde modal de búsqueda), devolver JSON
        if ($request->wantsJson()) {
            return response()->json($products2);
        }

        // La vista completa solo para Super Admin
        if (!Auth::user()->hasRole('Super Admin')) {
            abort(403);
        }

        $units = \App\Models\Unit::all(['id', 'name']);
        $formOptions = ['Agroquímicos', 'Fertilizantes'];
        $level3Options = ['fungicidas', 'insecticidas', 'acaricidas o misceláneos', 'herbicidas', 'foliares'];

        return Inertia::render('Products2', [
            'products2' => $products2,
            'units' => $units,
            'formOptions' => $formOptions,
            'level3Options' => $level3Options,
            'term' => $term,
        ]);
    }

    /**
     * Show the form for creating a new Product2.
     */
    public function create()
    {
        $units = Unit::all(['id', 'name']);
        // Los valores de form se pasarán desde el frontend
        return Inertia::render('Products2/Create', [
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created Product2 in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level3' => 'required|string|max:255',
            'active_ingredient' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'unit_price_id' => 'nullable|exists:units,id',
            'form' => 'nullable|string|max:255',
        ]);
        Product2::create($validated);
        return redirect()->route('products2.index')->with('success', 'Producto creado correctamente');
    }

    /**
     * Show the form for editing the specified Product2.
     */
    public function edit(Product2 $products2)
    {
        $units = Unit::all(['id', 'name']);
        return Inertia::render('Products2/Edit', [
            'product2' => $products2,
            'units' => $units,
        ]);
    }

    /**
     * Update the specified Product2 in storage.
     */
    public function update(Request $request, Product2 $products2)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level3' => 'required|string|max:255',
            'active_ingredient' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'unit_price_id' => 'nullable|exists:units,id',
            'form' => 'nullable|string|max:255',
        ]);
        $products2->update($validated);
        return redirect()->route('products2.index')->with('success', 'Producto actualizado correctamente');
    }

    /**
     * Remove the specified Product2 from storage.
     */
    public function destroy(Product2 $products2)
    {
        $products2->delete();
        return redirect()->route('products2.index')->with('success', 'Producto eliminado correctamente');
    }
}
