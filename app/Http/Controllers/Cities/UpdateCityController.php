<?php

namespace App\Http\Controllers\Cities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cities\FormCityRequest;
use App\Models\City;

class UpdateCityController extends Controller
{
    public function __invoke(City $city, FormCityRequest $request)
    {
        $city->name = $request->name;
        $city->is_active = $request->is_active ?? true;
        $city->save();
    }
}
