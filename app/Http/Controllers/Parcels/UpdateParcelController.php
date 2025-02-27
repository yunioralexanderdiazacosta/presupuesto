<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormParcelRequest;
use App\Models\Parcel;

class UpdateParcelController extends Controller
{
    public function __invoke(Parcel $parcel, FormParcelRequest $request)
    {
        $parcel->name = $request->name;
        $parcel->observations =$request->observations;
        $parcel->company_reason_id = $request->company_reason_id;
        $parcel->season_id = $request->season_id;
        $parcel->save();
    }
}
