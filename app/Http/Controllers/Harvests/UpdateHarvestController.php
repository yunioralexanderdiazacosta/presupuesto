<?php

namespace App\Http\Controllers\Harvests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Harvests\UpdateHarvestRequest;
use App\Models\Harvest;


class UpdateHarvestController extends Controller
{
    public function __invoke(Harvest $harvest, UpdateHarvestRequest $request)
    {

        $harvest->product_name = $request->product_name;
        $harvest->price        = $request->price;
        $harvest->unit_id_price= $request->unit_id_price;
        $harvest->observations = $request->observations;
        $harvest->subfamily_id = $request->subfamily_id;
        $harvest->unit_id      = $request->unit_id;
        $harvest->quantity     = $request->quantity;
        $harvest->team_id = auth()->user()->team_id;
        $harvest->season_id = session('season_id');
        $harvest->save();

        $harvest->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $harvest->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
