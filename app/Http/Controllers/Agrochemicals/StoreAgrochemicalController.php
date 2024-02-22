<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormAgrochemicalRequest;
use App\Models\Agrochemical;

class StoreAgrochemicalController extends Controller
{
    public function __invoke(FormAgrochemicalRequest $request)
    {
        $agrochemical = Agrochemical::create([
            'product_name'  => $request->product_name,
            'dose_type'     => $request->dose_type,
            'dose'          => $request->dose,
            'price'         => $request->price,
            'mojamiento'    => $request->mojamiento,
            'observations'  => $request->observations,
            'subfamily_id'  => $request->subfamily_id,
            'unit_id'       => $request->unit_id 
        ]);

        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $agrochemical->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
