<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrations\StoreAdministrationRequest;
use App\Models\Administration;
use Illuminate\Support\Facades\Auth;

class StoreAdministrationController extends Controller
{
    public function __invoke(StoreAdministrationRequest $request)
    {
        $products = $request->get('products');
          $user = Auth::user();
          $season_id = session('season_id'); // <-- Agrega esta línea

        foreach($products as $product){
            $administration = Administration::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'      => $product['quantity'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'team_id'       => $user->team_id,
                'season_id' => $season_id, // la temporada activa o seleccionada
                'subfamily_id'  => $request->subfamily_id, 
            ]);

            // Guardar los meses asociados en administration_items
            foreach($product['months'] as $month){
                $administration->items()->create([
                    'month_id' => $month
                ]);
            }
        }
    }
}
