<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\Month;
use Inertia\Inertia;

class SeasonsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $term = $request->term ?? ''; 

        $seasons = Season::with('month')->when($request->term, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        })->where('team_id', $user->team_id)->paginate(10)->withQueryString();

        $months = Month::get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
            ];
        });

        return Inertia::render('Seasons', compact('seasons', 'months', 'season_id', 'term'));
    }
}
