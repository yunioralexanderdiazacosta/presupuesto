<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Fruit;
use Inertia\Inertia;

class FruitsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? ''; 

        $fruits = Fruit::when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        return Inertia::render('Fruits', compact('fruits', 'term'));
    }
}
