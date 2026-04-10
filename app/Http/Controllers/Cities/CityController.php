<?php

namespace App\Http\Controllers\Cities;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CityController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = $request->term ?? '';

        $cities = City::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->paginate(1000)->withQueryString();

        return Inertia::render('Cities', compact('cities', 'term'));
    }
}
