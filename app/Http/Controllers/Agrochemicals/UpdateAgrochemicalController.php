<?php

namespace App\Http\Controllers\Agrochemicals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agrochemicals\UpdateAgrochemicalRequest;
use App\Models\Agrochemical;
use App\Traits\CheckSeasonLocked;

class UpdateAgrochemicalController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Agrochemical $agrochemical, UpdateAgrochemicalRequest $request)
    {
        $this->abortIfSeasonLocked();
         
        $agrochemical->product_name = $request->product_name;
        $agrochemical->dose         = $request->dose;
        $agrochemical->price        = $request->price;
        $agrochemical->unit_id_price= $request->unit_id_price;
        $agrochemical->mojamiento   = $request->mojamiento;
        $agrochemical->observations = $request->observations;
        $agrochemical->subfamily_id = $request->subfamily_id;
        $agrochemical->operation_id = $request->operation_id;
        $agrochemical->unit_id      = $request->unit_id;
        $agrochemical->dose_type_id = $request->dose_type_id;
        $agrochemical->team_id = \App\Models\User::find(auth()->id())->team_id;
        $agrochemical->season_id = session('season_id');
        $agrochemical->user_id = auth()->user()->id; // Asignar el ID del usuario autenticado
        $agrochemical->save(); 

        $agrochemical->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $agrochemical->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
