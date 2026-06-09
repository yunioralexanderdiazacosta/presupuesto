<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormParcelRequest;
use App\Models\Parcel;
use App\Traits\CheckSeasonLocked;

class StoreParcelController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(FormParcelRequest $request)
    {
        $this->abortIfSeasonLocked();
        $user = Auth::user();

        Parcel::create([
            'name'              => $request->name,
            'observations'      => $request->observations,
            'season_id'         => session('season_id'),
            'team_id'           => $user->team_id
        ]);   
    }
}
