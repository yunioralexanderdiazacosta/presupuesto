<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormParcelRequest;
use App\Models\Parcel;
use App\Traits\CheckSeasonLocked;

class UpdateParcelController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Parcel $parcel, FormParcelRequest $request)
    {
        $this->abortIfSeasonLocked();
        $parcel->name = $request->name;
        $parcel->observations =$request->observations;
        $parcel->save();
    }
}
