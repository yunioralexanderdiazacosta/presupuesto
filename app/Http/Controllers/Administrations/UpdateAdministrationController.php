<?php

namespace App\Http\Controllers\Administrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrations\UpdateAdministrationRequest;
use App\Models\Administration;
use App\Traits\CheckSeasonLocked;

class UpdateAdministrationController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Administration $administration, UpdateAdministrationRequest $request)
    {
        $this->abortIfSeasonLocked();
        $administration->product_name = $request->product_name;
        $administration->price        = $request->price;
        $administration->quantity     = $request->quantity;
        $administration->observations = $request->observations;
        $administration->subfamily_id = $request->subfamily_id;
        $administration->unit_id      = $request->unit_id;
        $administration->branch_id    = $request->branch_id ?: null;
        $administration->team_id   = \App\Models\User::find(auth()->id())->team_id;
        $administration->user_id   = auth()->user()->id;
        $administration->season_id = session('season_id');
        $administration->save();

        $administration->items()->delete();
        foreach($request->get('months') as $month){
            $administration->items()->create(['month_id' => $month]);
        }
        
    }
}
