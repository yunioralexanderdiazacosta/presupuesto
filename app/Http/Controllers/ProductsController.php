<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Unit;
use App\Models\Level1;
use App\Models\Product;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $units = Unit::get()->transform(function($unit){
            return [
                'label' => $unit->name,
                'value' => $unit->id
            ];
        });

        $season_id = session('season_id');

        $level1s = Level1::where('season_id', $season_id)->get()->transform(function($level){
            return [
                'label' => $level->name,
                'value' => $level->id
            ];
        });

        $term = $request->term ?? '';

        $products = Product::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->with('unit')->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        return Inertia::render('Products', compact('units', 'level1s', 'products', 'term'));
    }
}
