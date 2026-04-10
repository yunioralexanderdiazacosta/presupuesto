<?php

namespace App\Http\Controllers\Cities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cities\FormCityRequest;
use App\Models\City;

class StoreCityController extends Controller
{
    public function __invoke(FormCityRequest $request)
    {
        City::create([
            'name' => $request->name,
            'is_active' => $request->is_active ?? true,
        ]);
    }
}
