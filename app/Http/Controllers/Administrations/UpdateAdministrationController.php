<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrations\UpdateAdministrationRequest;
use App\Models\Administration;

class UpdateAdministrationController extends Controller
{
    public function __invoke(Administration $administration, UpdateAdministrationRequest $request)
    {
        $administration->product_name = $request->product_name;
        $administration->price        = $request->price;
        $administration->quantity     = $request->quantity;
        $administration->observations = $request->observations;
        $administration->subfamily_id = $request->subfamily_id;
        $administration->unit_id      = $request->unit_id;
         $administration->team_id      = $request->team_id;
        $administration->save(); 

        $administration->items()->delete();
        foreach($request->get('months') as $month){
            $administration->items()->create(['month_id' => $month]);
        }
        
    }
}
