<?php

namespace App\Http\Controllers\ManPowers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManPowers\UpdateManPowerRequest;
use Illuminate\Http\Request;
use App\Models\ManPower;
use App\Traits\CheckSeasonLocked;

class UpdateManPowerController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(ManPower $manPower, UpdateManPowerRequest $request)
    {
        $this->abortIfSeasonLocked();
        $manPower->product_name = $request->product_name;
        $manPower->workday      = $request->workday;
        $manPower->price        = $request->price;
        $manPower->observations = $request->observations;
        $manPower->subfamily_id = $request->subfamily_id;
        $manPower->operation_id = $request->operation_id;
        $manPower->team_id = \App\Models\User::find(auth()->id())->team_id;
        $manPower->user_id = auth()->user()->id; // Asignar el ID del usuario autenticado
        // Obtener el season_id desde la sesión (o del request si lo prefieres)
        $manPower->season_id = session('season_id');
        // Si prefieres desde el request: $manPower->season_id = $request->season_id;
        $manPower->save();

        $manPower->items()->detach();
        foreach($request->get('cc') as $cc){
            foreach($request->get('months') as $month){
                $manPower->items()->attach($cc, ['month_id' => $month]);
            }
        }
    }
}
