<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agrochemicals\StoreAgrochemicalRequest;
use App\Models\Agrochemical;
use Illuminate\Support\Facades\Auth;

class StoreAgrochemicalController extends Controller
{
    public function __invoke(StoreAgrochemicalRequest $request)
    {
        $products = $request->get('products');
          $user = Auth::user();

        foreach($products as $product){
            $agrochemical = Agrochemical::create([
                'product_name'  => $product['product_name'],
                'dose'          => $product['dose'],
                'price'         => $product['price'],
                'unit_id_price' => $product['unit_id_price'],
                'mojamiento'    => $product['mojamiento'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'dose_type_id'  => $product['dose_type_id'],
                'subfamily_id'  => $request->subfamily_id, 
                'team_id'       => $user->team_id,
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $agrochemical->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }
}
