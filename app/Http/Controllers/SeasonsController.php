<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use App\Models\Month;
use Inertia\Inertia;

class SeasonsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $season_id = session('season_id');

        $seasons = Season::with('month')->where('team_id', $user->team_id)->paginate(10);

        $months = Month::get()->transform(function($month){
            return [
                'label' => $month->name,
                'value' => $month->id
            ];
        });

        return Inertia::render('Seasons', compact('seasons', 'months', 'season_id'));
    }
}
