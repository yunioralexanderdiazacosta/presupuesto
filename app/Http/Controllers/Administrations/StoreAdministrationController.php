<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrations\StoreAdministrationRequest;
use App\Models\Administration;

class StoreAdministrationController extends Controller
{
    public function __invoke(StoreAdministrationRequest $request)
    {
        $products = $request->get('products');

        foreach($products as $product){
            $administration = Administration::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'     => $product['quantity'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
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
