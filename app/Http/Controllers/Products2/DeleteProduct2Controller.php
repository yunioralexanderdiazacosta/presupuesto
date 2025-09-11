<?php

namespace App\Http\Controllers\Products2;

use App\Http\Controllers\Controller;
use App\Models\Product2;
use Illuminate\Http\Request;

class DeleteProduct2Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin');
    }

    public function __invoke(Product2 $products2)
    {
        $products2->delete();
        return redirect()->route('products2.index')->with('success', 'Producto eliminado correctamente');
    }
}
