<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Level1;

class LevelsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $term = $request->term ?? '';  

        $season_id = session('season_id');

        $levels = Level1::where('team_id', $user->team_id)->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('season_id', $season_id)->paginate(10)->withQueryString();

        return Inertia::render('Levels', compact('levels', 'term'));
    }
}
