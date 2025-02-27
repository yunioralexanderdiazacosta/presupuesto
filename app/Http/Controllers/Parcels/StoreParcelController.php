<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\FormParcelRequest;
use App\Models\Parcel;

class StoreParcelController extends Controller
{
    public function __invoke(FormParcelRequest $request)
    {
        $user = Auth::user();

        Parcel::create([
            'name'              => $request->name,
            'observations'      => $request->observations,
            'company_reason_id' => $request->company_reason_id,
            'season_id'         => $request->season_id,
            'team_id'           => $user->team_id
        ]);   
    }
}
