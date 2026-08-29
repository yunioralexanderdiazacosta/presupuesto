<?php

namespace App\Http\Controllers\Fertilizers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fertilizers\UpdateFertilizerRequest;
use App\Models\Fertilizer;
use App\Traits\CheckSeasonLocked;

class UpdateFertilizerController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Fertilizer $fertilizer, UpdateFertilizerRequest $request)
    {
        $this->abortIfSeasonLocked();
        $fertilizer->product_name = $request->product_name;
        $fertilizer->dose         = $request->dose;
        $fertilizer->price        = $request->price;
        $fertilizer->observations = $request->observations;
        $fertilizer->subfamily_id = $request->subfamily_id;
        $fertilizer->operation_id = $request->operation_id;
        $fertilizer->unit_id      = $request->unit_id;
        $fertilizer->unit_id_price= $request->unit_id_price;
        $fertilizer->team_id = \App\Models\User::find(auth()->id())->team_id;
        $fertilizer->season_id = session('season_id');
        $fertilizer->user_id = auth()->user()->id; // Asignar el ID del usuario autenticado
        $fertilizer->save();

        $fertilizer->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $fertilizer->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
