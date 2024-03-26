<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManPowers\StoreManPowerRequest;
use App\Models\ManPower;
use Illuminate\Http\Request;

class StoreManPowerController extends Controller
{
    public function __invoke(StoreManPowerRequest $request)
    {
        $products = $request->get('products');

        foreach($products as $product){
            $manpower = ManPower::create([
                'product_name'  => $product['product_name'],
                'workday'       => $product['workday'],
                'price'         => $product['price'],
                'observations'  => $product['observations'],
                'subfamily_id'  => $request->subfamily_id,
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $manpower->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }





}
