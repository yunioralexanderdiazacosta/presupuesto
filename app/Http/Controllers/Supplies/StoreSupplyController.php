<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplies\StoreSupplyRequest;
use App\Models\Supply;

class StoreSupplyController extends Controller
{
    public function __invoke(StoreSupplyRequest $request)
    {
        $products = $request->get('products');

        foreach($products as $product){
            $supply = Supply::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'         => $product['quantity'],
                'unit_id_price' => $product['unit_id_price'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'subfamily_id'  => $request->subfamily_id, 
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $supply->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }





}
