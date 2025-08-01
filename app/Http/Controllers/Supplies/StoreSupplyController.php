<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplies\StoreSupplyRequest;
use App\Models\Supply;
use Illuminate\Support\Facades\Auth;

class StoreSupplyController extends Controller
{
    public function __invoke(StoreSupplyRequest $request)
    {
        $products = $request->get('products');
         $user = Auth::user();
        $season_id = session('season_id'); // la temporada activa o seleccionada

        foreach($products as $product){
            $supply = Supply::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'      => $product['quantity'],
                'unit_id_price' => $product['unit_id_price'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'subfamily_id'  => $request->subfamily_id, 
                'team_id'       => $user->team_id,
                'season_id' => $season_id // la temporada activa o seleccionada

            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $supply->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }





}
