<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class StoreServiceController extends Controller
{
    public function __invoke(StoreServiceRequest $request)
    {
        $products = $request->get('products');
   $user = Auth::user();
 $season_id = session('season_id'); // la temporada activa o seleccionada
        foreach($products as $product){
            $service = Service::create([
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
                    $service->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }

}
