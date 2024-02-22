<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormAgrochemicalRequest;
use App\Models\Agrochemical;

class UpdateAgrochemicalController extends Controller
{
    public function __invoke(Agrochemical $agrochemical, FormAgrochemicalRequest $request)
    {
         
        $agrochemical->product_name = $request->product_name;
        $agrochemical->dose_type    = $request->dose_type;
        $agrochemical->dose         = $request->dose;
        $agrochemical->price        = $request->price;
        $agrochemical->mojamiento   = $request->mojamiento;
        $agrochemical->observations = $request->observations;
        $agrochemical->subfamily_id = $request->subfamily_id;
        $agrochemical->unit_id      = $request->unit_id;
        $agrochemical->save(); 

        $agrochemical->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $agrochemical->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
