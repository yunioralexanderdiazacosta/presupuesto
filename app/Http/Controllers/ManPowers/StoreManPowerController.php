<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManPowers\StoreManPowerRequest;
use App\Models\ManPower;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreManPowerController extends Controller
{
    public function __invoke(StoreManPowerRequest $request)
    {
        $products = $request->get('products');
          $user = Auth::user();
 $season_id = session('season_id'); // la temporada activa o seleccionada

        $unit = Unit::where('name', 'JH')->first();
       

        foreach($products as $product){
            $manpower = ManPower::create([
                'product_name'  => $product['product_name'],
                'workday'       => $product['workday'],
                'price'         => $product['price'],
                'observations'  => $product['observations'],
                'subfamily_id'  => $request->subfamily_id,
                'team_id'       => $user->team_id,
                 'season_id' => $season_id, // la temporada activa o seleccionada
                'unit_id'       => $unit->id
            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $manpower->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }





}
