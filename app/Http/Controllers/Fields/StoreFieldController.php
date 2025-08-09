<?php

namespace App\Http\Controllers\Fields;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Fields\StoreFieldRequest;
use Illuminate\Http\Request;
use App\Models\Field;

class StoreFieldController extends Controller
{
    public function __invoke(StoreFieldRequest $request)
    {
        $products = $request->get('products');

        $user = Auth::user();
$season_id = session('season_id'); // la temporada activa o seleccionada
        
        foreach($products as $product){
            $field = Field::create([
                'product_name'  => $product['product_name'],
                'price'         => $product['price'],
                'quantity'      => $product['quantity'],
                'observations'  => $product['observations'],
                'unit_id'       => $product['unit_id'],
                'subfamily_id'  => $request->subfamily_id,
                'team_id'       => $user->team_id,
                'user_id'       => $user->id, // Asignar el ID del usuario autenticado 'user_id'
                'season_id' => $season_id // la temporada activa o seleccionada
            ]);

            // Guardar los meses asociados en administration_items
            foreach($product['months'] as $month){
                $field->items()->create([
                    'month_id' => $month
                ]);
            }
        }
    }
}
