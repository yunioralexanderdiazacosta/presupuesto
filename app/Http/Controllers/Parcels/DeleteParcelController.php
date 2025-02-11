<?php

namespace App\Http\Controllers\Parcels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parcel;

class DeleteParcelController extends Controller
{
    public function __invoke(Parcel $parcel)
    {
        $parcel->delete();
    }
}
