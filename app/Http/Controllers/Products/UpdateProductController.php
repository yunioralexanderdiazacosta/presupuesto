<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormProductRequest;
use Illuminate\Http\Request;
use App\Models\Product;

class UpdateProductController extends Controller
{
    public function __invoke(Product $product, FormProductRequest $request)
    {
        $product->name      = $request->name;
        $product->unit_id   = $request->unit_id;
        $product->level1_id = $request->level1_id;
        $product->level2_id = $request->level2_id;
        $product->level3_id = $request->level3_id;
        $product->level4_id = $request->level4_id;
        $product->save();
    }
}
