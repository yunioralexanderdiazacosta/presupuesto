<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class DeleteProductController extends Controller
{
    public function __invoke(Product $product)
    {
        $product->delete();
    }
}
