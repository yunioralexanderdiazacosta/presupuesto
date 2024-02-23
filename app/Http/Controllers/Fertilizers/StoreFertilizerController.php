<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormFertilizerRequest;
use Illuminate\Http\Request;
use App\Models\Fertilizer;

class StoreFertilizerController extends Controller
{
    public function __invoke(FormFertilizerRequest $request)
    {
        $fertilizer = Fertilizer::create([
            'product_name'  => $request->product_name,
            'dose'          => $request->dose,
            'price'         => $request->price,
            'observations'  => $request->observations,
            'subfamily_id'  => $request->subfamily_id,
            'unit_id'       => $request->unit_id 
        ]);

        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $fertilizer->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
