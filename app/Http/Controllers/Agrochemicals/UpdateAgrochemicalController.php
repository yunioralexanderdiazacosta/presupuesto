<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agrochemicals\UpdateAgrochemicalRequest;
use App\Models\Agrochemical;

class UpdateAgrochemicalController extends Controller
{
    public function __invoke(Agrochemical $agrochemical, UpdateAgrochemicalRequest $request)
    {
         
        $agrochemical->product_name = $request->product_name;
        $agrochemical->dose         = $request->dose;
        $agrochemical->price        = $request->price;
        $agrochemical->unit_id_price= $request->unit_id_price;
        $agrochemical->mojamiento   = $request->mojamiento;
        $agrochemical->observations = $request->observations;
        $agrochemical->subfamily_id = $request->subfamily_id;
        $agrochemical->unit_id      = $request->unit_id;
        $agrochemical->dose_type_id = $request->dose_type_id;
         $agrochemical->team_id      = $request->team_id;
        $agrochemical->save(); 

        $agrochemical->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $agrochemical->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
