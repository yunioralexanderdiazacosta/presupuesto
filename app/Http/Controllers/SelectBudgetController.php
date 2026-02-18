<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Season;
use Inertia\Inertia;

class SelectBudgetController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        \Log::info('═══ SELECT BUDGET CONTROLLER ═══', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'team_id' => $user->team_id,
            'current_session_season_id' => session('season_id'),
            'has_session_season_id' => session()->has('season_id'),
        ]);

        $seasons = Season::select('id', 'name')->where('team_id', $user->team_id)->get()->transform(function($season){
            return [
                'label' => $season->name,
                'value' => $season->id
            ];
        });

        \Log::info('SELECT BUDGET: Seasons loaded', [
            'seasons_count' => $seasons->count(),
            'seasons' => $seasons->toArray(),
        ]);

        return Inertia::render('SelectBudget', compact('seasons'));
    }
}
