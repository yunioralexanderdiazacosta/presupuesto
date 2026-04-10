<?php

namespace App\Http\Controllers\Cities;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Contract;

class DeleteCityController extends Controller
{
    public function __invoke(City $city)
    {
        $contracts = Contract::where('city_id', $city->id)->count();
        if ($contracts > 0) {
            return back()->with('error', "No se puede eliminar \"{$city->name}\" porque tiene {$contracts} contrato(s) asociado(s).");
        }

        $city->delete();
        return back()->with('success', 'Ciudad eliminada correctamente.');
    }
}
