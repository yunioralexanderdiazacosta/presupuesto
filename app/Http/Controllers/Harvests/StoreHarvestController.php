<?php

namespace App\Http\Controllers\Harvests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Harvests\StoreHarvestRequest;
use App\Models\Harvest;
use Illuminate\Support\Facades\Auth;
use App\Traits\CheckSeasonLocked;

class StoreHarvestController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(StoreHarvestRequest $request)
    {
        $this->abortIfSeasonLocked();
        $products = $request->get('products');
   $user = Auth::user();
 $season_id = session('season_id'); // la temporada activa o seleccionada
        foreach($products as $product){
            $harvest = Harvest::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'      => $product['quantity'],
                'unit_id_price' => $product['unit_id_price'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'subfamily_id'  => $request->subfamily_id, 
                'operation_id'  => $request->operation_id,
                'investment_id' => $request->investment_id ?: null,
                'team_id'       => $user->team_id,
                'user_id'       => $user->id, // Asignar el ID del usuario autenticado 'user_id'
                'season_id' => $season_id // la temporada activa o seleccionada
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $harvest->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }

}