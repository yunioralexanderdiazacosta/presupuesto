<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplies\UpdateSupplyRequest;
use App\Models\Supply;

class UpdateSupplyController extends Controller
{
    public function __invoke(Supply $supply, UpdateSupplyRequest $request)
    {
         
        $supply->product_name = $request->product_name;
        $supply->price        = $request->price;
        $supply->unit_id_price= $request->unit_id_price;
        $supply->observations = $request->observations;
        $supply->subfamily_id = $request->subfamily_id;
        $supply->unit_id      = $request->unit_id;
        $supply->quantity     = $request->quantity;
        $supply->save(); 

        $supply->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $supply->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
