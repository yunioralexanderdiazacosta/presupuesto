<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Unit;
use App\Models\Level1;
use App\Models\Product;
use Inertia\Inertia;

use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function __invoke()
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

        $products = Product::with('unit')->where('team_id', $user->team_id)->paginate(10);

        return Inertia::render('Products', compact('units', 'level1s', 'products'));
    }
}
