<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Month;
use App\Models\Budget;
use App\Models\User;
use App\Models\Season;

class BudgetsController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();


        $season_id = session('season_id');

        $budgets = Budget::with('season')->where('team_id', $user->team_id)->where('season_id', $season_id)->paginate(10);

        return Inertia::render('Budgets', compact('budgets'));
    }
}
