<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityApiController extends Controller
{
    public function index()
    {
        return City::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:cities,name']);

        $city = City::create(['name' => $request->name]);

        return response()->json(['id' => $city->id, 'name' => $city->name]);
    }

    public function destroy(City $city)
    {
        // Es un catálogo global: evitar que se borre si algún equipo la tiene en uso.
        if (\App\Models\Contract::where('city_id', $city->id)->exists()) {
            return response()->json(['message' => 'No se puede eliminar: la ciudad está en uso en uno o más contratos.'], 422);
        }

        $city->delete();
        return response()->json(['success' => true]);
    }
}
