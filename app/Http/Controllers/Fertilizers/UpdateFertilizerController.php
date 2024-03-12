<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fertilizers\UpdateFertilizerRequest;
use App\Models\Fertilizer;

class UpdateFertilizerController extends Controller
{
    public function __invoke(Fertilizer $fertilizer, UpdateFertilizerRequest $request)
    {
        $fertilizer->product_name = $request->product_name;
        $fertilizer->dose         = $request->dose;
        $fertilizer->price        = $request->price;
        $fertilizer->observations = $request->observations;
        $fertilizer->subfamily_id = $request->subfamily_id;
        $fertilizer->unit_id      = $request->unit_id;
        $fertilizer->save(); 

        $fertilizer->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $fertilizer->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
