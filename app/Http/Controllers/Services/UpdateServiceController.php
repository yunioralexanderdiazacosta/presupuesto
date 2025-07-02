<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Service;


class UpdateServiceController extends Controller
{
    public function __invoke(Service $service, UpdateServiceRequest $request)
    {
         
        $service->product_name = $request->product_name;
        $service->price        = $request->price;
        $service->unit_id_price= $request->unit_id_price;
        $service->observations = $request->observations;
        $service->subfamily_id = $request->subfamily_id;
        $service->unit_id      = $request->unit_id;
        $service->quantity     = $request->quantity;
         $service->team_id = auth()->user()->team_id;
          $service->season_id = session('season_id');
        $service->save(); 

        $service->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $service->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
