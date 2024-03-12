<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fertilizers\StoreFertilizerRequest;
use Illuminate\Http\Request;
use App\Models\Fertilizer;

class StoreFertilizerController extends Controller
{
    public function __invoke(StoreFertilizerRequest $request)
    {
        $products = $request->get('products');

        foreach($products as $product){
            $fertilizer = Fertilizer::create([
                'product_name'  => $product['product_name'],
                'dose'          => $product['dose'],
                'price'         => $product['price'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'subfamily_id'  => $request->subfamily_id,
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $fertilizer->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }
}
