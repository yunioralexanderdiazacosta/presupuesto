<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Service;
use App\Traits\CheckSeasonLocked;


class UpdateServiceController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Service $service, UpdateServiceRequest $request)
    {
        $this->abortIfSeasonLocked();
         
        $service->product_name = $request->product_name;
        $service->price        = $request->price;
        $service->unit_id_price= $request->unit_id_price;
        $service->observations = $request->observations;
        $service->subfamily_id = $request->subfamily_id;
        $service->operation_id = $request->operation_id;
        $service->unit_id      = $request->unit_id;
        $service->quantity     = $request->quantity;
        $service->team_id = \App\Models\User::find(auth()->id())->team_id;
        $service->user_id = auth()->user()->id; // Asignar el ID del usuario autenticado
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
