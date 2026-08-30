<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManPowers\StoreManPowerRequest;
use App\Models\ManPower;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\CheckSeasonLocked;

class StoreManPowerController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(StoreManPowerRequest $request)
    {
        $this->abortIfSeasonLocked();
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
                'operation_id'  => $request->operation_id,
                'investment_id' => $request->investment_id ?: null,
                'team_id'       => $user->team_id,
                 'season_id' => $season_id, // la temporada activa o seleccionada
                'unit_id'       => $unit->id,
                 'user_id'       => $user->id, // Asignar el ID del usuario autenticado 'user_id'

            ]);

            foreach($request->get('cc') as $cc){
                foreach($product['months'] as $month){
                    $manpower->items()->attach($cc, ['month_id' => $month]);
                }
            }
        }
    }





}
