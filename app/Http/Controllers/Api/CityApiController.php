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
        $city->delete();
        return response()->json(['success' => true]);
    }
}
