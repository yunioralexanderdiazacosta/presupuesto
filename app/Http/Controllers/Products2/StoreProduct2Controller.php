<?php

namespace App\Http\Controllers\Products2;

use App\Http\Controllers\Controller;
use App\Models\Product2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreProduct2Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin');
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level3' => 'required|string|max:255',
            'active_ingredient' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'unit_price_id' => 'nullable|integer|exists:units,id',
            'form' => 'nullable|string|max:255',
        ]);
        Product2::create($validated);
        return redirect()->route('products2.index')->with('success', 'Producto creado correctamente');
    }
}
